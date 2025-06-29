<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CemeteryBooking;
use App\Models\User;
use App\Models\Cemetery;
use App\Models\Plot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <-- THIS IS CORRECT!

class CemeteryBookingController extends Controller
{
public function index(Request $request)
{
    $statuses = ['pending', 'approved', 'rejected'];
    $status = $request->query('status', '');

    // Get the current cemetery user
    $user = Auth::user();
    // Find the cemetery profile this user owns
    $cemetery = \App\Models\Cemetery::where('user_id', $user->id)->first();

    if (!$cemetery) {
        // Optionally: return a view with an error, or redirect, or show an empty bookings list
        return view('cemetery.bookings.index', [
            'bookings' => collect(),
            'statuses' => $statuses,
            'status' => $status,
            'cemeteryMissing' => true,
        ]);
    }

    // Only bookings for this cemetery
    $query = \App\Models\CemeteryBooking::with(['user', 'cemetery.user'])
        ->where('cemetery_id', $cemetery->id);

    if ($status && in_array($status, $statuses)) {
        $query->where('status', $status);
    }

    $bookings = $query->orderByDesc('created_at')->paginate(15);

    return view('cemetery.bookings.index', [
        'bookings' => $bookings,
        'statuses' => $statuses,
        'status' => $status,
        'cemeteryMissing' => false,
    ]);
}

    /**
     * Display the specified booking.
     */

public function show($id)
{
    $cemeteryBooking = CemeteryBooking::with([
        'plot',
        'user',
        'cemetery.user',
        'funeralBooking.funeralHome',
        'funeralBooking.package',
    ])->findOrFail($id);

    // Get funeral booking details (decode JSON)
    $details = [];
    if ($cemeteryBooking->funeralBooking && $cemeteryBooking->funeralBooking->details) {
        $details = is_array($cemeteryBooking->funeralBooking->details)
            ? $cemeteryBooking->funeralBooking->details
            : json_decode($cemeteryBooking->funeralBooking->details, true);
    }

    // Fix: Get plots for THIS cemetery (not user!), where status is available
    $availablePlots = [];
    if ($cemeteryBooking->cemetery) {
        $availablePlots = \App\Models\Plot::where('cemetery_id', $cemeteryBooking->cemetery->id)
            ->where('status', 'available')
            ->get();
    }

    return view('cemetery.bookings.show', compact('cemeteryBooking', 'details', 'availablePlots'));
}






    /**
     * Handle approve/reject (and plot assignment) of a cemetery booking.
     */
public function approve(Request $request, $id)
{
    $request->validate([
        'plot_id' => 'required|exists:plots,id',
    ]);

    DB::transaction(function () use ($request, $id) {
        $cemeteryBooking = CemeteryBooking::with(['funeralBooking', 'cemetery.user'])->findOrFail($id);

        // Related Funeral Booking
        $booking = $cemeteryBooking->funeralBooking;
        if (!$booking) {
            throw new \Exception('No related funeral booking found.');
        }

        // Decode details from booking
        $details = is_array($booking->details)
            ? $booking->details
            : json_decode($booking->details, true);

        // Update cemetery booking
        $cemeteryBooking->status = 'approved';
        $cemeteryBooking->plot_id = $request->plot_id;
        $cemeteryBooking->save();

        // Update Plot
        $plot = Plot::findOrFail($request->plot_id);
        $plot->status = 'reserved';
        $plot->owner_id = $booking->client_user_id; // Set owner as the client
        $plot->deceased_name = $details['deceased_name'] ?? null;
        $plot->save();

        // Update or Insert into booking_details table
        DB::table('booking_details')
            ->updateOrInsert(
                ['booking_id' => $booking->id],
                ['plot_id' => $plot->id]
            );

        // ---- Gather names for more descriptive notifications ----
        $client         = User::find($booking->client_user_id);
        $clientName     = $client->name ?? 'Client';
        $funeralParlor  = User::find($booking->funeral_home_id);
        $cemetery       = $cemeteryBooking->cemetery;
        $cemeteryName   = $cemetery?->user?->name ?? 'Cemetery';
        $plotNumber     = $plot->plot_number ?? 'N/A';

        // ---- Notify client ----
        if ($client) {
            $notifData = [
                'title'         => 'Cemetery Booking Approved',
                'message'       => "Dear <b>{$clientName}</b>, your cemetery booking at <b>{$cemeteryName}</b> has been <b>APPROVED</b> and plot <b>#{$plotNumber}</b> assigned.",
                'booking_id'    => $cemeteryBooking->id,
                'plot_number'   => $plotNumber,
                'client_name'   => $clientName,
                'cemetery_name' => $cemeteryName,
                'role'          => 'client',
            ];
            $client->notify(new \App\Notifications\CemeteryBookingApproved($notifData));
        }

        // ---- Notify funeral parlor ----
        if ($funeralParlor) {
            $notifData = [
                'title'         => 'Cemetery Booking Approved (Client)',
                'message'       => "A cemetery booking for <b>{$clientName}</b> at <b>{$cemeteryName}</b> has been <b>APPROVED</b>. Plot assigned: <b>#{$plotNumber}</b>.",
                'booking_id'    => $cemeteryBooking->id,
                'plot_number'   => $plotNumber,
                'client_name'   => $clientName,
                'cemetery_name' => $cemeteryName,
                'role'          => 'funeral',
            ];
            $funeralParlor->notify(new \App\Notifications\CemeteryBookingApproved($notifData));
        }

        // ---- Notify agent using the helper ----
        $agentUser = $cemeteryBooking->actualAgentUser();
        if ($agentUser) {
            $notifData = [
                'title'         => 'Cemetery Booking Approved (Client)',
                'message'       => "Your client <b>{$clientName}</b> had a cemetery booking approved at <b>{$cemeteryName}</b>. Plot assigned: <b>#{$plotNumber}</b>.",
                'booking_id'    => $cemeteryBooking->id,
                'plot_number'   => $plotNumber,
                'client_name'   => $clientName,
                'cemetery_name' => $cemeteryName,
                'role'          => 'agent',
            ];
            $agentUser->notify(new \App\Notifications\CemeteryBookingApproved($notifData));
            \Log::info('[DEBUG] Notification sent to agent user.', [
                'agent_user_id' => $agentUser->id,
                'agent_email'   => $agentUser->email,
            ]);
        } else {
            \Log::info('[DEBUG] No agent user found to notify for this cemetery booking.', [
                'cemeteryBooking_id' => $cemeteryBooking->id
            ]);
        }
    });

    return redirect()->route('cemetery.bookings.index')->with('success', 'Booking approved and plot assigned.');
}




public function reject(Request $request, $id)
{
    $cemeteryBooking = \App\Models\CemeteryBooking::with(['funeralBooking', 'cemetery.user'])->findOrFail($id);

    // Set status to rejected, clear plot
    $cemeteryBooking->status = 'rejected';
    $cemeteryBooking->plot_id = null;
    $cemeteryBooking->save();

    // Get related funeral booking
    $booking = $cemeteryBooking->funeralBooking;

    // ---- Gather names for more descriptive notifications ----
    $client         = $booking ? \App\Models\User::find($booking->client_user_id) : null;
    $clientName     = $client?->name ?? 'Client';
    $funeralParlor  = $booking ? \App\Models\User::find($booking->funeral_home_id) : null;
    $cemetery       = $cemeteryBooking->cemetery;
    $cemeteryName   = $cemetery?->user?->name ?? 'Cemetery';

    // ---- Notify client ----
    if ($client) {
        $notifData = [
            'title'         => 'Cemetery Booking Rejected',
            'message'       => "Dear <b>{$clientName}</b>, your cemetery booking at <b>{$cemeteryName}</b> has been <b>REJECTED</b>. Please contact support for more information.",
            'booking_id'    => $cemeteryBooking->id,
            'client_name'   => $clientName,
            'cemetery_name' => $cemeteryName,
            'role'          => 'client',
        ];
        $client->notify(new \App\Notifications\CemeteryBookingRejected($notifData));
    }

    // ---- Notify agent using the helper ----
    $agentUser = $cemeteryBooking->actualAgentUser();
    if ($agentUser) {
        $notifData = [
            'title'         => 'Cemetery Booking Rejected (Client)',
            'message'       => "Your client <b>{$clientName}</b> had a cemetery booking at <b>{$cemeteryName}</b> that was <b>REJECTED</b>.",
            'booking_id'    => $cemeteryBooking->id,
            'client_name'   => $clientName,
            'cemetery_name' => $cemeteryName,
            'role'          => 'agent',
        ];
        $agentUser->notify(new \App\Notifications\CemeteryBookingRejected($notifData));
        \Log::info('[DEBUG] Notification sent to agent user.', [
            'agent_user_id' => $agentUser->id,
            'agent_email'   => $agentUser->email,
        ]);
    } else {
        \Log::info('[DEBUG] No agent user found to notify for this cemetery booking.', [
            'cemeteryBooking_id' => $cemeteryBooking->id
        ]);
    }

    return redirect()->route('cemetery.bookings.index')
        ->with('success', 'Booking has been rejected.');
}


}
