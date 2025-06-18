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

        // POST: handle registration
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser) {
            \Log::warning('Agent Complete Invite - Email Already Registered', [
                'email' => $data['email'],
            ]);
            return redirect()->route('login')->with('error', 'This email is already registered. Please log in.');
        }

        $agent = User::create([
            'name' => $request->name,
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'role' => 'agent',
            'remember_token' => Str::random(10),
        ]);

        // Link to funeral parlor via pivot table with status = active
        DB::table('funeral_home_agent')->insert([
            'funeral_user_id' => $data['funeral_user_id'],
            'agent_user_id'   => $agent->id,
            'status'          => 'active',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        \Log::info('Agent Registered and Linked', [
            'agent_id' => $agent->id,
            'agent_email' => $agent->email,
            'funeral_home_id' => $data['funeral_user_id'],
        ]);

        auth()->login($agent);

        return redirect()->route('dashboard')->with('success', 'Welcome! Your agent account has been created.');
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
}
