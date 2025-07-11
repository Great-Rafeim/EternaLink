<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CemeteryBooking;
use App\Models\Plot;
use App\Models\Cemetery;
use App\Models\User;
use App\Models\Booking;
use App\Notifications\CemeteryBookingSubmitted;
use App\Notifications\CemeteryBookingAgentNotify;


class ClientToCemeteryController extends Controller
{
public function index(Request $request)
{
    $query = \App\Models\User::where('role', 'cemetery')->with('cemetery');

    if ($search = $request->input('q')) {
        $query->where(function ($q2) use ($search) {
            $q2->where('name', 'like', "%{$search}%")
               ->orWhereHas('cemetery', function ($cemQ) use ($search) {
                   $cemQ->where('address', 'like', "%{$search}%");
               });
        });
    }

    $cemeteryUsers = $query->orderBy('created_at', 'desc')->paginate(8);

    return view('client.cemeteries.index', compact('cemeteryUsers'));
}

public function booking($userId)
{
    $user = User::where('id', $userId)->where('role', 'cemetery')->firstOrFail();
    $cemetery = $user->cemetery;
    $client = auth()->user();

    // List of client's funeral bookings (with funeral home, package, and booking details loaded)
    $bookings = \App\Models\Booking::with(['funeralHome', 'package', 'bookingDetail'])
        ->where('client_user_id', $client->id)
        ->get();

    // Map of booking_id => BookingDetail for quick JS autofill
    $bookingDetails = \App\Models\BookingDetail::whereIn('booking_id', $bookings->pluck('id'))
        ->get()
        ->keyBy('booking_id');

    // Plots in this cemetery with status = 'reserved' or 'occupied' and owner is this client (if applicable)
    $ownedPlots = \App\Models\Plot::where('cemetery_id', $cemetery->id)
        ->whereIn('status', ['reserved', 'occupied'])
        ->where('deceased_name', $client->name)
        ->get();

    return view('client.cemeteries.booking', compact('user', 'cemetery', 'bookings', 'ownedPlots', 'bookingDetails'));
}

public function submitBooking(Request $request, $cemeteryUserId)
{
    \Log::info('[DEBUG] submitBooking called.', [
        'auth_user_id'    => auth()->id(),
        'cemeteryUserId'  => $cemeteryUserId,
        'request'         => $request->all(),
    ]);

    try {
        // 1. Validate input
        $validated = $request->validate([
            'booking_id'              => 'required|exists:bookings,id',
            'casket_size'             => 'required|string|max:100',
            'interment_date'          => 'required|date|after:today',
            'death_certificate'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'burial_permit'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'construction_permit'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'has_plot'                => 'required|in:0,1',
            'proof_of_purchase'       => 'required_if:has_plot,1|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ], [
            'proof_of_purchase.required_if' => 'Proof of purchase is required if you already own a plot.',
        ]);
        \Log::info('[DEBUG] Validation passed.', ['validated' => $validated]);

        // 2. Find the selected cemetery user
        $cemeteryUser = \App\Models\User::where('id', $cemeteryUserId)
            ->where('role', 'cemetery')
            ->with('cemetery')
            ->first();

        if (!$cemeteryUser || !$cemeteryUser->cemetery) {
            \Log::warning('[DEBUG] Cemetery user or cemetery model not found.', [
                'cemeteryUserId' => $cemeteryUserId
            ]);
            return back()->withErrors(['error' => 'Cemetery or user not found.']);
        }

        // 3. Upload files and save paths
        $death_certificate_path    = $request->file('death_certificate')->store('cemetery_documents', 'public');
        $burial_permit_path       = $request->file('burial_permit')->store('cemetery_documents', 'public');
        $construction_permit_path = $request->file('construction_permit')->store('cemetery_documents', 'public');
        $proof_of_purchase_path   = null;
        if ($request->hasFile('proof_of_purchase')) {
            $proof_of_purchase_path = $request->file('proof_of_purchase')->store('cemetery_documents', 'public');
        }
        \Log::info('[DEBUG] Files uploaded.', [
            'death_certificate_path'    => $death_certificate_path,
            'burial_permit_path'        => $burial_permit_path,
            'construction_permit_path'  => $construction_permit_path,
            'proof_of_purchase_path'    => $proof_of_purchase_path,
        ]);

        // 4. Create the cemetery booking with real data
        $cemeteryBooking = \App\Models\CemeteryBooking::create([
            'user_id'                  => auth()->id(),
            'cemetery_id'              => $cemeteryUser->cemetery->id,
            'booking_id'               => $request->booking_id,
            'casket_size'              => $request->casket_size,
            'interment_date'           => $request->interment_date,
            'status'                   => 'pending',
            'death_certificate_path'   => $death_certificate_path,
            'burial_permit_path'       => $burial_permit_path,
            'construction_permit_path' => $construction_permit_path,
            'proof_of_purchase_path'   => $proof_of_purchase_path,
        ]);

        \Log::info('[DEBUG] CemeteryBooking created.', [
            'cemeteryBooking_id' => $cemeteryBooking->id,
            'cemetery_id'        => $cemeteryUser->cemetery->id,
            'booking_id'         => $request->booking_id,
        ]);

        // 5. Notify the cemetery user (owner)
        $cemeteryUser->notify(new \App\Notifications\CemeteryBookingSubmitted($cemeteryBooking->id));
        \Log::info('[DEBUG] Notification sent to cemetery user.', [
            'cemetery_user_id' => $cemeteryUser->id
        ]);

        // 6. Notify the agent assigned to the funeral booking (if any)
        $agentUser = $cemeteryBooking->actualAgentUser();
        if ($agentUser) {
            $agentUser->notify(new \App\Notifications\CemeteryBookingAgentNotify($cemeteryBooking));
            \Log::info('[DEBUG] Notification sent to agent user.', [
                'agent_user_id' => $agentUser->id,
                'agent_email'   => $agentUser->email,
            ]);
        } else {
            \Log::info('[DEBUG] No agent user found to notify for this cemetery booking.', [
                'cemeteryBooking_id' => $cemeteryBooking->id
            ]);
        }

        return redirect()->route('client.cemeteries.index')
            ->with('success', 'Cemetery booking submitted! Notifications sent.');

    } catch (\Throwable $e) {
        \Log::error('[ERROR] submitBooking failed.', [
            'exception' => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);
        return back()->withErrors(['error' => 'A server error occurred. Please try again or contact support.']);
    }
}



/*
public function submitBooking(Request $request, $cemeteryUserId)
{
    \Log::info('[DEBUG] submitBooking called.', [
        'auth_user_id'    => auth()->id(),
        'cemeteryUserId'  => $cemeteryUserId,
        'request'         => $request->all(),
    ]);

    try {
        // 1. Find the selected cemetery user and their cemetery model
        $cemeteryUser = \App\Models\User::where('id', $cemeteryUserId)
            ->where('role', 'cemetery')
            ->with('cemetery')
            ->first();

        if (!$cemeteryUser || !$cemeteryUser->cemetery) {
            \Log::warning('[DEBUG] Cemetery user or cemetery model not found.', [
                'cemeteryUserId' => $cemeteryUserId
            ]);
            return response()->json(['error' => 'Cemetery or user not found.'], 404);
        }

        // 2. Set funeral booking_id to 5 (for test/dummy mode)
        $booking_id = 5;

        // 3. Create the new cemetery booking
        $cemeteryBooking = \App\Models\CemeteryBooking::create([
            'user_id'                  => auth()->id(),
            'cemetery_id'              => $cemeteryUser->cemetery->id,
            'booking_id'               => $booking_id,
            'casket_size'              => 'Standard (dummy)',
            'interment_date'           => now()->addDays(3),
            'status'                   => 'pending',
            'death_certificate_path'   => 'dummy/path/death.pdf',
            'burial_permit_path'       => 'dummy/path/burial.pdf',
            'construction_permit_path' => 'dummy/path/construction.pdf',
            'proof_of_purchase_path'   => 'dummy/path/purchase.pdf',
        ]);

        \Log::info('[DEBUG] CemeteryBooking created.', [
            'cemeteryBooking_id' => $cemeteryBooking->id,
            'cemetery_id'        => $cemeteryUser->cemetery->id,
            'booking_id'         => $booking_id,
        ]);

        // 4. Notify the cemetery user (owner)
        $cemeteryUser->notify(new \App\Notifications\CemeteryBookingSubmitted($cemeteryBooking->id));
        \Log::info('[DEBUG] Notification sent to cemetery user.', [
            'cemetery_user_id' => $cemeteryUser->id
        ]);

        // 5. Notify the agent assigned to the funeral booking (if any)
        $agentUser = $cemeteryBooking->actualAgentUser();
        if ($agentUser) {
            $agentUser->notify(new \App\Notifications\CemeteryBookingAgentNotify($cemeteryBooking));
            \Log::info('[DEBUG] Notification sent to agent user.', [
                'agent_user_id' => $agentUser->id,
                'agent_email'   => $agentUser->email,
            ]);
        } else {
            \Log::info('[DEBUG] No agent user found to notify for this cemetery booking.', [
                'cemeteryBooking_id' => $cemeteryBooking->id
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Notifications sent.']);

    } catch (\Throwable $e) {
        \Log::error('[ERROR] submitBooking failed.', [
            'exception' => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => 'Server error.'], 500);
    }
}
*/




public function cancelCemeteryBooking($id)
{
    $cemeteryBooking = \App\Models\CemeteryBooking::where('id', $id)
        ->where('user_id', auth()->id())
        ->where('status', 'pending')
        ->firstOrFail();

    $cemeteryBooking->update(['status' => 'cancelled']);

    return redirect()->back()->with('success', 'Cemetery booking cancelled.');
}
public function show($id)
{
    $cemeteryBooking = \App\Models\CemeteryBooking::with([
        'plot',
        'cemetery.user',
        'user',
        'funeralBooking'
    ])->findOrFail($id);

    // Get related funeral booking
    $booking = $cemeteryBooking->funeralBooking;

    // Decode details field (if present)
    $details = [];
    if ($booking && $booking->details) {
        $details = is_array($booking->details)
            ? $booking->details
            : json_decode($booking->details, true);
    }

    return view('client.cemeteries.show', compact('cemeteryBooking', 'booking', 'details'));
}


}
