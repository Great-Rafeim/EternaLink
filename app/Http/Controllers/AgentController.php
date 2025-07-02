<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Models\User;
use App\Mail\AgentInvitationMail;

class AgentController extends Controller
{
    // 1. List Agents with Pivot Status 
    public function index()
    {
        \Log::info('AGENT INDEX HIT', ['user_id' => auth()->id()]);

        $agents = DB::table('users')
            ->join('funeral_home_agent', function ($join) {
                $join->on('users.id', '=', 'funeral_home_agent.agent_user_id')
                    ->where('funeral_home_agent.funeral_user_id', auth()->id());
            })
            ->where('users.role', 'agent')
            ->whereNull('users.deleted_at')
            ->select('users.*', 'funeral_home_agent.status as pivot_status')
            ->get();

        \Log::info('AGENTS FETCHED', ['count' => $agents->count()]);

        return view('funeral.agents.index', compact('agents'));
    }

    // 2. Invite/Send Signed URL to Agent
public function invite(Request $request)
{
    \Log::debug('Invite request received', [
        'request_data' => $request->all(),
        'initiator_id' => auth()->id(),
    ]);

    $request->validate([
        'email' => ['required', 'email'],
    ]);

    $email = $request->email;
    $funeralUser = auth()->user();

    // 1. Check if the user exists
    $existingUser = \App\Models\User::where('email', $email)->first();

    if ($existingUser) {
        if ($existingUser->role !== 'agent') {
            return back()->with('error', 'The email belongs to an existing user who is not an agent.');
        }
        // 2. Check if already linked to this parlor
        $alreadyLinked = \DB::table('funeral_home_agent')
            ->where('funeral_user_id', $funeralUser->id)
            ->where('agent_user_id', $existingUser->id)
            ->exists();
        if ($alreadyLinked) {
            return back()->with('error', 'This agent is already linked to your funeral parlor.');
        }
        // Do NOT link yet! Just log intent.
        \Log::info('Agent exists and not yet linked to this parlor. Invitation email will be sent.', [
            'funeral_user_id' => $funeralUser->id,
            'agent_user_id' => $existingUser->id,
            'invited_email' => $email,
        ]);
    } else {
        // No user yet; invitation will lead to account creation
        \Log::info('No user found, invite will create new agent account', [
            'invited_email' => $email,
        ]);
    }

    // Always generate an invite URL for consistency (for both new and existing)
    $inviteData = [
        'email' => $email,
        'funeral_user_id' => $funeralUser->id,
        'invite_token' => Str::random(32),
    ];

    \Log::debug('Invite data prepared', $inviteData);

    $signedUrl = URL::temporarySignedRoute(
        'agents.accept-invite',
        now()->addHours(48),
        ['invite' => base64_encode(json_encode($inviteData))]
    );

    \Log::info('Agent Invitation Signed URL Generated', [
        'funeral_home_id' => $funeralUser->id,
        'funeral_email' => $funeralUser->email,
        'invited_email' => $email,
        'signed_url' => $signedUrl,
        'invite_token' => $inviteData['invite_token'],
        'expires_at' => now()->addHours(48)->toDateTimeString(),
    ]);

    try {
        Mail::to($email)->send(new AgentInvitationMail($signedUrl, $funeralUser));
        \Log::info('Agent Invitation Email Sent', [
            'funeral_home_id' => $funeralUser->id,
            'funeral_email' => $funeralUser->email,
            'invited_email' => $email,
        ]);
    } catch (\Exception $e) {
        \Log::error('Failed to send agent invitation email', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'invited_email' => $email,
            'signed_url' => $signedUrl,
        ]);
        return back()->with('error', 'Failed to send invitation email. Please try again.');
    }

    \Log::debug('Invite function completed', [
        'result' => 'success',
        'invited_email' => $email,
    ]);

    return back()->with('success', 'Invitation sent successfully.');
}




public function acceptInvite(Request $request, $invite)
{
    \Log::info('Agent Invite Link Accessed or Submitted', [
        'full_url' => $request->fullUrl(),
        'method' => $request->method(),
        'signature' => $request->query('signature'),
        'expires' => $request->query('expires'),
        'has_valid_signature' => $request->hasValidSignature(),
    ]);

    if (! $request->hasValidSignature()) {
        \Log::warning('Agent Invitation Invalid Signature', [
            'full_url' => $request->fullUrl(),
            'signature' => $request->query('signature'),
            'expires' => $request->query('expires'),
        ]);
        abort(403, 'Invitation link is invalid or expired.');
    }

    $data = json_decode(base64_decode($invite), true);
    $funeralUserId = $data['funeral_user_id'];
    $email = $data['email'];

    // --- Check if agent user already exists
    $existingUser = \App\Models\User::where('email', $email)->first();

    if ($existingUser) {
        // If not agent, block
        if ($existingUser->role !== 'agent') {
            \Log::warning('Agent Complete Invite - Email Registered but not agent', [
                'email' => $email,
            ]);
            return redirect()->route('login')->with('error', 'This email is already registered but not as an agent.');
        }

        // If agent, check if already linked
        $alreadyLinked = \DB::table('funeral_home_agent')
            ->where('funeral_user_id', $funeralUserId)
            ->where('agent_user_id', $existingUser->id)
            ->exists();

        if (! $alreadyLinked) {
            // Link the agent to this funeral home (status: active)
            \DB::table('funeral_home_agent')->insert([
                'funeral_user_id' => $funeralUserId,
                'agent_user_id' => $existingUser->id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \Log::info('Existing agent linked to new funeral parlor via invite.', [
                'agent_user_id' => $existingUser->id,
                'funeral_user_id' => $funeralUserId,
            ]);
        } else {
            \Log::info('Agent already linked to this funeral parlor, skipping insert.', [
                'agent_user_id' => $existingUser->id,
                'funeral_user_id' => $funeralUserId,
            ]);
        }

        // Log the user in (if not already logged in)
        if (!auth()->check() || auth()->id() !== $existingUser->id) {
            auth()->login($existingUser);
        }

        return redirect()->route('agent.dashboard')
            ->with('success', 'You are now linked to the funeral parlor and redirected to your dashboard.');
    }

    // --- Show registration form for new agent
    if ($request->isMethod('get')) {
        return view('funeral.agents.accept-invite', [
            'email' => $email,
            'invite' => $invite,
        ]);
    }

    // --- POST: Registration for new agent
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    \DB::beginTransaction();

    try {
        $agent = \App\Models\User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => \Hash::make($request->password),
            'role' => 'agent',
            'remember_token' => \Str::random(10),
        ]);

        // Link to funeral parlor via pivot table with status = active
        \DB::table('funeral_home_agent')->insert([
            'funeral_user_id' => $funeralUserId,
            'agent_user_id'   => $agent->id,
            'status'          => 'active',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // --- (Optional) Update booking_agents table for THIS booking only ---
        if (!empty($data['booking_id'])) {
            \DB::table('booking_agents')
                ->where('booking_id', $data['booking_id'])
                ->update([
                    'agent_user_id' => $agent->id,
                    'updated_at' => now(),
                ]);
        }

        // --- (Optional) Update agent_client_requests status for THIS booking only ---
        if (!empty($data['client_user_id']) && !empty($data['booking_id'])) {
            \DB::table('agent_client_requests')
                ->where('client_id', $data['client_user_id'])
                ->where('booking_id', $data['booking_id'])
                ->where('status', 'pending')
                ->whereNull('agent_id')
                ->update([
                    'agent_id' => $agent->id,
                    'status' => 'accepted',
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        \DB::commit();

        \Log::info('Agent Registered and Linked to booking', [
            'agent_id' => $agent->id,
            'agent_email' => $agent->email,
            'funeral_home_id' => $funeralUserId,
            'client_user_id' => $data['client_user_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
        ]);

        auth()->login($agent);

        return redirect()->route('agent.dashboard')->with('success', 'Welcome! Your agent account has been created.');
    } catch (\Exception $e) {
        \DB::rollBack();
        \Log::error('Agent Invite Registration Failed', [
            'error' => $e->getMessage(),
        ]);
        return back()->with('error', 'There was a problem creating your agent account. Please try again.');
    }
}




    // 4. Edit Agent (returns info via AJAX or modal; no separate view needed)
    public function getAgent(Request $request, $agentId)
    {
        $agent = DB::table('users')
            ->join('funeral_home_agent', function ($join) {
                $join->on('users.id', '=', 'funeral_home_agent.agent_user_id')
                    ->where('funeral_home_agent.funeral_user_id', auth()->id());
            })
            ->where('users.role', 'agent')
            ->where('users.id', $agentId)
            ->whereNull('users.deleted_at')
            ->select('users.*', 'funeral_home_agent.status as pivot_status')
            ->first();

        if (!$agent) {
            abort(404);
        }
        // You can return as JSON for AJAX editing if desired
        return response()->json($agent);
    }

    // 5. Update Agent Info + Status in Pivot Table
    public function update(Request $request, $agentId)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Update name in users
        $agent = User::where('role', 'agent')->findOrFail($agentId);
        $agent->update([
            'name' => $request->name,
        ]);

        // Update status in pivot table
        DB::table('funeral_home_agent')
            ->where('funeral_user_id', auth()->id())
            ->where('agent_user_id', $agentId)
            ->update([
                'status' => $request->status,
                'updated_at' => now(),
            ]);

        \Log::info('Agent Updated', [
            'agent_id' => $agent->id,
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return redirect()->route('funeral.agents.index')->with('success', 'Agent updated successfully.');
    }

    // 6. Soft Delete Agent (optional: could also just deactivate via pivot)
    public function destroy($agentId)
    {
        $agent = User::where('role', 'agent')->findOrFail($agentId);
        $agent->delete(); // Soft delete in users

        // Optionally, also set status in pivot to 'inactive':
        DB::table('funeral_home_agent')
            ->where('funeral_user_id', auth()->id())
            ->where('agent_user_id', $agentId)
            ->update([
                'status' => 'inactive',
                'updated_at' => now(),
            ]);

        return redirect()->route('funeral.agents.index')->with('success', 'Agent deleted (deactivated) successfully.');
    }

public function inviteClientAgent(Request $request, $bookingId)
{
    $booking = \App\Models\Booking::with('client', 'bookingAgent')->findOrFail($bookingId);
    $bookingAgent = $booking->bookingAgent;
    $email = $bookingAgent->client_agent_email ?? null;

    if (!$email) {
        return back()->with('error', 'No client agent email found for this booking.');
    }

    // Prevent duplicate pending invitations for same client and email/booking
    $pendingExists = \DB::table('agent_client_requests')
        ->where('client_id', $booking->client_user_id)
        ->where('booking_id', $booking->id)
        ->where('agent_id', null)
        ->where('status', 'pending')
        ->where('notes', 'like', '%Invitation sent by funeral parlor%')
        ->exists();

    if ($pendingExists) {
        return back()->with('info', 'Invitation already sent and pending for this client agent email.');
    }

    $funeralUser = auth()->user();
    $inviteData = [
        'email'          => $email,
        'funeral_user_id'=> $funeralUser->id,
        'invite_token'   => \Str::random(32),
        'client_user_id' => $booking->client_user_id,
        'booking_id'     => $booking->id, // <-- INCLUDE BOOKING ID!
    ];
    $signedUrl = \URL::temporarySignedRoute(
        'agents.accept-invite',
        now()->addHours(48),
        ['invite' => base64_encode(json_encode($inviteData))]
    );

    // Track in agent_client_requests (now includes booking_id)
    \DB::table('agent_client_requests')->insert([
        'client_id'   => $booking->client_user_id,
        'booking_id'  => $booking->id,
        'agent_id'    => null,
        'status'      => 'pending',
        'requested_at'=> now(),
        'notes'       => 'Invitation sent by funeral parlor.',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // Send invitation email
    \Mail::to($email)->send(
        new \App\Mail\AgentInvitationMail($signedUrl, $funeralUser, $booking->client ?? null, $booking)
    );

    return back()->with('success', 'Agent invitation sent to the provided email.');
}


public function assignFuneralAgent(Request $request, $bookingId)
{
    $request->validate([
        'agent_user_id' => 'required|exists:users,id',
    ]);

    $booking = \App\Models\Booking::findOrFail($bookingId);

    // 1. Ensure the agent belongs to this funeral home and is active
    $isLinked = \DB::table('funeral_home_agent')
        ->where('funeral_user_id', $booking->funeral_home_id)
        ->where('agent_user_id', $request->agent_user_id)
        ->where('status', 'active')
        ->exists();

    // 2. Ensure the agent is not assigned to another booking for this funeral parlor (ignore bookings from other parlors)
    $isAssignedToThisParlor = \DB::table('booking_agents')
        ->join('bookings', 'booking_agents.booking_id', '=', 'bookings.id')
        ->where('booking_agents.agent_user_id', $request->agent_user_id)
        ->where('bookings.funeral_home_id', $booking->funeral_home_id)
        ->where('booking_agents.booking_id', '!=', $booking->id) // Allow reassignment to THIS booking
        ->exists();

    if (!$isLinked) {
        return back()->with('error', 'Selected agent is not registered to this funeral parlor.');
    }

    if ($isAssignedToThisParlor) {
        return back()->with('error', 'Selected agent is already assigned to another booking for this funeral parlor.');
    }

    $booking->bookingAgent()->updateOrCreate(
        ['booking_id' => $booking->id], 
        ['agent_user_id' => $request->agent_user_id]
    );

    return back()->with('success', 'Agent assigned to this booking successfully.');
}




}
