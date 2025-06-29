<?php
// app/Http/Controllers/CemeteryDashboardController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Cemetery;
use App\Models\Plot;
use App\Models\CemeteryBooking;

class CemeteryDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role !== 'cemetery') {
            abort(403, 'Unauthorized access.');
        }

        // Get THIS user's cemetery
        $cemetery = Cemetery::where('user_id', $user->id)->first();

        if (!$cemetery) {
            // No cemetery record assigned
            return view('cemetery.dashboard', [
                'cemeteryMissing' => true
            ]);
        }

        $cemeteryId = $cemetery->id;

        // Stats for ONLY this cemetery
        $availablePlots = Plot::where('cemetery_id', $cemeteryId)->where('status', 'available')->count();
        $reservedPlots  = Plot::where('cemetery_id', $cemeteryId)->where('status', 'reserved')->count();
        $occupiedPlots  = Plot::where('cemetery_id', $cemeteryId)->where('status', 'occupied')->count();

        $pendingBookings = CemeteryBooking::where('cemetery_id', $cemeteryId)
            ->where('status', 'pending')->count();

        $recentActivities = CemeteryBooking::with(['plot'])
            ->where('cemetery_id', $cemeteryId)
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(function ($booking) {
                return (object)[
                    'description' => 'Booking for Plot #' . optional($booking->plot)->plot_number
                        . ' (' . ucfirst($booking->status) . ')',
                    'created_at'  => $booking->updated_at,
                ];
            });

        return view('cemetery.dashboard', [
            'cemeteryMissing' => false,
            'availablePlots' => $availablePlots,
            'reservedPlots' => $reservedPlots,
            'occupiedPlots' => $occupiedPlots,
            'pendingBookings' => $pendingBookings,
            'recentActivities' => $recentActivities,
            'cemeteryName' => $cemetery->name ?? 'Your Cemetery'
        ]);
    }
}
