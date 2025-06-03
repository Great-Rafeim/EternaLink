<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Ensure2FAIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (
            $user && 
            $user->two_factor_secret && 
            !$request->session()->get('2fa_passed') && 
            !$request->is('2fa/verify') &&
            !$request->is('logout')
        ) {
            return redirect()->route('2fa.verify.form');
        }

        return $next($request);
    }
}


