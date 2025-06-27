<?php
// Example: app/Http/Controllers/ClientDashboardController.php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Plot;
use App\Models\CemeteryBooking;


class CemeteryDashboardController extends Controller
{

public function index()
{
    // Only allow cemetery users to access this dashboard
    $user = Auth::user();

    if ($user->role !== 'cemetery') {
        abort(403, 'Unauthorized access.');
        // Or: return redirect('/')->with('error', 'Unauthorized.');
    }

    // The dashboard only shows counts for all data (since there's no cemetery_id)
    $availablePlots = Plot::where('status', 'available')->count();
    $reservedPlots  = Plot::where('status', 'reserved')->count();
    $occupiedPlots  = Plot::where('status', 'occupied')->count();

    $pendingBookings = CemeteryBooking::where('status', 'pending')->count();

    // Recent activity: last 6 bookings (all statuses)
    $recentActivities = CemeteryBooking::with(['plot'])
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

    return view('cemetery.dashboard', compact(
        'availablePlots',
        'reservedPlots',
        'occupiedPlots',
        'pendingBookings',
        'recentActivities'
    ));
}

}

