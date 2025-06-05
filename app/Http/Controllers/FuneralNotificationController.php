<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FuneralNotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(10);
        return view('funeral.notifications.index', compact('notifications'));
    }
}
