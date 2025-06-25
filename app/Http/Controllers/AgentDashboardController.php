<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginHistory;

class AgentDashboardController extends Controller
{
    public function index()
    {
        return view('agent.dashboard');
    }

}