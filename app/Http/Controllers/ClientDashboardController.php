<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['package', 'funeralHome', 'agent'])
            ->where('client_user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('client.dashboard', compact('bookings'));
    }

    // Booking detail view
    public function show($id)
    {
        $booking = Booking::with(['package', 'funeralHome', 'agent'])
            ->where('client_user_id', Auth::id())
            ->findOrFail($id);

        return view('client.bookings.show', compact('booking'));
    }

    public function cancel($bookingId)
    {
        $booking = Booking::where('client_user_id', auth()->id())->findOrFail($bookingId);

        if (!in_array($booking->status, ['pending', 'confirmed', 'assigned'])) {
            return back()->with('error', 'This booking cannot be canceled.');
        }

        $booking->status = 'canceled';
        $booking->save();

        return back()->with('success', 'Your booking has been canceled.');
    }


}

