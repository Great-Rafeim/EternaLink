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
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $email = $request->email;
        $funeralUser = auth()->user();

        $inviteData = [
            'email' => $email,
            'funeral_user_id' => $funeralUser->id,
            'invite_token' => Str::random(32),
        ];

        $signedUrl = URL::temporarySignedRoute(
            'agents.accept-invite',
            now()->addHours(48),
            ['invite' => base64_encode(json_encode($inviteData))]
        );

        \Log::info('Agent Invitation Generated', [
            'funeral_home_id' => $funeralUser->id,
            'funeral_email' => $funeralUser->email,
            'invited_email' => $email,
            'signed_url' => $signedUrl,
            'invite_token' => $inviteData['invite_token'],
            'expires_at' => now()->addHours(48)->toDateTimeString(),
        ]);

        Mail::to($email)->send(new AgentInvitationMail($signedUrl, $funeralUser));

        \Log::info('Agent Invitation Sent', [
            'funeral_home_id' => $funeralUser->id,
            'funeral_email' => $funeralUser->email,
            'invited_email' => $email,
        ]);

        return back()->with('success', 'Invitation sent successfully.');
    }

// 3. Accept Invite + Complete Registration (GET and POST)
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

    if ($request->isMethod('get')) {
        // Show the registration form
        return view('funeral.agents.accept-invite', [
            'email' => $data['email'],
            'invite' => $invite,
        ]);
    }

    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $existingUser = \App\Models\User::where('email', $data['email'])->first();
    if ($existingUser) {
        \Log::warning('Agent Complete Invite - Email Already Registered', [
            'email' => $data['email'],
        ]);
        return redirect()->route('login')->with('error', 'This email is already registered. Please log in.');
    }

    \DB::beginTransaction();

    try {
        $agent = \App\Models\User::create([
            'name' => $request->name,
            'email' => $data['email'],
            'password' => \Hash::make($request->password),
            'role' => 'agent',
            'remember_token' => \Str::random(10),
        ]);

        // Link to funeral parlor via pivot table with status = active
        \DB::table('funeral_home_agent')->insert([
            'funeral_user_id' => $data['funeral_user_id'],
            'agent_user_id'   => $agent->id,
            'status'          => 'active',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // --- Update booking_agents table for THIS booking only ---
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
            'funeral_home_id' => $data['funeral_user_id'],
            'client_user_id' => $data['client_user_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
        ]);

        auth()->login($agent);

        return redirect()->route('dashboard')->with('success', 'Welcome! Your agent account has been created.');
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

    // Ensure the agent belongs to this funeral home and is not assigned to another booking
    $isAvailable = \DB::table('funeral_home_agent')
        ->where('funeral_user_id', $booking->funeral_home_id)
        ->where('agent_user_id', $request->agent_user_id)
        ->where('status', 'active')
        ->exists()
        &&
        !\DB::table('booking_agents')->where('agent_user_id', $request->agent_user_id)->exists();

    if (!$isAvailable) {
        return back()->with('error', 'Selected agent is either not registered to this funeral parlor or already assigned.');
    }

    $booking->bookingAgent()->updateOrCreate(
        ['booking_id' => $booking->id], 
        ['agent_user_id' => $request->agent_user_id]
    );

    return back()->with('success', 'Agent assigned to this booking successfully.');
}



}
