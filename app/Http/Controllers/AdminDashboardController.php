<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginHistory;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalUsers'    => User::count(),
            'funeralCount'  => User::where('role', 'funeral')->count(),
            'cemeteryCount' => User::where('role', 'cemetery')->count(),
            'logins'        => LoginHistory::with('user')->latest()->limit(10)->get(),
        ]);
    }

    public function loginHistory(Request $request)
    {
        $logins = LoginHistory::with('user')->latest()->paginate(20);

        return view('admin.login-history.index', compact('logins'));
    }
}
    