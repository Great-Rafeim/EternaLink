<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Notifications\PendingBusinessRegistration;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');

        $roles = ['client', 'funeral', 'cemetery'];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:' . implode(',', $roles)],
        ];

        if (in_array($role, ['funeral', 'cemetery'])) {
            $rules['proof_of_ownership'] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'];
            $rules['government_id'] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'];
        }

        $validated = $request->validate($rules);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->password = Hash::make($validated['password']);
        $user->status = in_array($user->role, ['funeral', 'cemetery']) ? 'pending' : 'approved';

        // Handle file uploads with unique yet human-readable filenames
        if (in_array($user->role, ['funeral', 'cemetery'])) {
            $originalProof = $request->file('proof_of_ownership')->getClientOriginalName();
            $originalId = $request->file('government_id')->getClientOriginalName();
            $proofFilename = time() . '_proof_' . uniqid() . '.' . $request->file('proof_of_ownership')->extension();
            $idFilename = time() . '_id_' . uniqid() . '.' . $request->file('government_id')->extension();

            $user->proof_of_ownership = $request->file('proof_of_ownership')->storeAs('business_proofs', $proofFilename, 'public');
            $user->government_id = $request->file('government_id')->storeAs('government_ids', $idFilename, 'public');
        }

        $user->save();

        event(new Registered($user));

        // Notify all admins using queued notifications
        if ($user->status === 'pending') {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify((new PendingBusinessRegistration($user))->onQueue('notifications'));
            }
        }

        if ($user->status === 'pending') {
            // Registration submitted, wait for admin approval
            return redirect()->route('login')->with('status', 'Registration submitted! Please wait for administrator approval.');
        }

        // If client or agent, auto-login
        Auth::login($user);
        return redirect(route('dashboard', absolute: false));
    }
}
