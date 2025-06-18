<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // ✅ Redirect to force password change if needed
        if ($user->must_change_password) {
            return redirect()->route('password.change.form')->with('force_password', 'Your password must be changed now.');
        }

        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'client':
                return redirect()->route('client.dashboard');
            case 'funeral':
                return redirect()->route('funeral.dashboard');
            case 'cemetery':
                return redirect()->route('cemetery.dashboard');
            default:
                return redirect('/'); // or abort(403)
        }

    }

    public function redirectToDashboard(): RedirectResponse
    {

     \Log::emergency('THIS IS AN EMERGENCY LOG');
   
        $user = Auth::user();

        switch ($user->role) {
            case 'client':
                return redirect()->route('client.dashboard');
            case 'funeral':
                return redirect()->route('funeral.dashboard');
            case 'cemetery':
                return redirect()->route('cemetery.dashboard');
            case 'admin':
                return redirect()->route('admin.dashboard');
            default:
                return redirect('/');
        }
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->forget('2fa_passed'); // <-- Clear 2FA session


        $request->session()->regenerateToken();

        return redirect('/');

    }

    
}
