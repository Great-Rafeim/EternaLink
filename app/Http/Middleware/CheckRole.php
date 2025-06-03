<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|array  $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            // Not authenticated
            abort(403, 'User not authenticated.');
        }

        // Check if user role is in the allowed roles
        if (!in_array($user->role, $roles)) {
            abort(403, 'User does not have the right roles.');
        }

        return $next($request);
    }
}
