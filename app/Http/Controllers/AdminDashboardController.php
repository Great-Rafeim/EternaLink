<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginHistory;

class AdminDashboardController extends Controller
{
public function index()
{
    // Get the most recent 10 logins
    $logins = \App\Models\LoginHistory::with('user')->latest()->limit(10)->get();

    // Pending registration requests: status = 'pending', role = funeral/cemetery/agent
    $pendingRequests = \App\Models\User::where('status', 'pending')
        ->whereIn('role', ['funeral', 'cemetery', 'agent'])
        ->latest()
        ->get();

    return view('admin.dashboard', [
        'totalUsers'      => \App\Models\User::count(),
        'clientCount'     => \App\Models\User::where('role', 'client')->count(),
        'agentCount'      => \App\Models\User::where('role', 'agent')->count(),
        'funeralCount'    => \App\Models\User::where('role', 'funeral')->count(),
        'cemeteryCount'   => \App\Models\User::where('role', 'cemetery')->count(),
        'logins'          => $logins,
        'pendingRequests' => $pendingRequests,
    ]);
}


public function loginHistory(Request $request)
{
    $logins = \App\Models\LoginHistory::with('user')->latest()->paginate(20);
    return view('admin.login-history.index', compact('logins'));
}

}
    