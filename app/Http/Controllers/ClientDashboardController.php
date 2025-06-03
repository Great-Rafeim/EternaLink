<?php
// Example: app/Http/Controllers/ClientDashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index()
    {
        return view('client.dashboard');
    }
}
