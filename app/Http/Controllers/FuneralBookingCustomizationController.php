<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CustomizedPackage;
use Illuminate\Http\Request;

class FuneralBookingCustomizationController extends Controller
{
    // Show the customization request details
    public function show($bookingId)
    {
        $booking = Booking::with(['client', 'package', 'funeralHome'])->findOrFail($bookingId);

        // Only allow if this is their funeral home
        if ($booking->funeral_home_id !== auth()->id()) abort(403);
 $customized = CustomizedPackage::where('booking_id', $booking->id)->with('items')->firstOrFail();

        return view('funeral.bookings.customization.show', compact('booking', 'customized'));
    }

    // Approve the request
    public function approve(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        if ($booking->funeral_home_id !== auth()->id()) abort(403);

        $customized = CustomizedPackage::where('booking_id', $booking->id)->firstOrFail();
        $customized->status = 'approved';
        $customized->save();

        // Optionally: update booking's package_id or details

        // Notify client
        $booking->client->notify(new \App\Notifications\CustomizationRequestApproved($customized));
    // Also notify agent if assigned
    $agent = $booking->agent;
    if ($agent) {
        $agent->notify(new \App\Notifications\CustomizationRequestApproved($customized));
    }
       
        return redirect()->route('funeral.bookings.index')->with('success', 'Customization approved.');
    }

    // Deny the request
    public function deny(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        if ($booking->funeral_home_id !== auth()->id()) abort(403);

        $customized = CustomizedPackage::where('booking_id', $booking->id)->firstOrFail();
        $customized->status = 'denied';
        $customized->save();

        // Notify client
        $booking->client->notify(new \App\Notifications\CustomizationRequestDenied($customized));
    // Also notify agent if assigned
    $agent = $booking->agent;
    if ($agent) {
        $agent->notify(new \App\Notifications\CustomizationRequestDenied($customized));
    }

        return redirect()->route('funeral.bookings.index')->with('error', 'Customization denied.');
    }
}
