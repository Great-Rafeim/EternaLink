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


public function submitBooking(Request $request, $userId)
{
    \Log::info('[DEBUG] submitBooking started.', ['userId' => $userId, 'request' => $request->all()]);

    try {
        $user = User::where('id', $userId)->where('role', 'cemetery')->firstOrFail();
        \Log::info('[DEBUG] Cemetery user found.', ['cemetery_user' => $user->id]);

        $cemetery = $user->cemetery;
        \Log::info('[DEBUG] Cemetery loaded.', ['cemetery' => $cemetery ? $cemetery->id : null]);

        // Validation
        $validated = $request->validate([
            'booking_id'              => 'required|exists:bookings,id',
            'casket_size'             => 'required|string|max:100',
            'interment_date'          => 'required|date|after:today',
            'death_certificate'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'burial_permit'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'construction_permit'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'has_plot'                => 'required|in:0,1',
            'proof_of_purchase'       => 'required_if:has_plot,1|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'proof_of_purchase.required_if' => 'Proof of purchase is required if you already own a plot.',
        ]);
        \Log::info('[DEBUG] Validation passed.', ['validated' => $validated]);

        // Upload files and save paths
        $death_certificate_path    = $request->file('death_certificate')->store('cemetery_documents', 'public');
        $burial_permit_path       = $request->file('burial_permit')->store('cemetery_documents', 'public');
        $construction_permit_path = $request->file('construction_permit')->store('cemetery_documents', 'public');
        $proof_of_purchase_path   = null;
        if ($request->hasFile('proof_of_purchase')) {
            $proof_of_purchase_path = $request->file('proof_of_purchase')->store('cemetery_documents', 'public');
        }
        \Log::info('[DEBUG] File upload complete.', [
            'death_certificate_path'    => $death_certificate_path,
            'burial_permit_path'        => $burial_permit_path,
            'construction_permit_path'  => $construction_permit_path,
            'proof_of_purchase_path'    => $proof_of_purchase_path,
        ]);

        // Save booking (new columns, no plot_id, stores file paths)
        $cemeteryBooking = CemeteryBooking::create([
            'user_id'                  => auth()->id(),
            'cemetery_id'              => $cemetery->id,
            'booking_id'               => $request->booking_id,
            'casket_size'              => $request->casket_size,
            'interment_date'           => $request->interment_date,
            'status'                   => 'pending',
            'death_certificate_path'   => $death_certificate_path,
            'burial_permit_path'       => $burial_permit_path,
            'construction_permit_path' => $construction_permit_path,
            'proof_of_purchase_path'   => $proof_of_purchase_path,
        ]);
        \Log::info('[DEBUG] CemeteryBooking created.', ['cemeteryBooking_id' => $cemeteryBooking->id]);

        // Notify cemetery user (owner)
        $cemeteryUser = $user;
        $cemeteryUser->notify((new CemeteryBookingSubmitted($cemeteryBooking))->onQueue('notifications'));
        \Log::info('[DEBUG] Notified cemetery user.', ['cemetery_user_id' => $cemeteryUser->id]);

        // Notify agent assigned to the funeral booking (from booking_agents)
        $bookingAgent = \DB::table('booking_agents')
            ->where('booking_id', $request->booking_id)
            ->whereNotNull('agent_user_id')
            ->orderByDesc('id')
            ->first();

        if ($bookingAgent && $bookingAgent->agent_user_id) {
            $agent = User::find($bookingAgent->agent_user_id);
            if ($agent) {
                $agent->notify((new CemeteryBookingAgentNotify($cemeteryBooking))->onQueue('notifications'));
                \Log::info('[DEBUG] Notified agent.', ['agent_user_id' => $agent->id]);
            } else {
                \Log::warning('[DEBUG] Agent not found for booking.', ['booking_agent_id' => $bookingAgent->id]);
            }
        } else {
            \Log::info('[DEBUG] No agent to notify for booking.', ['booking_id' => $request->booking_id]);
        }

        return redirect()->route('client.cemeteries.index')
            ->with('success', 'Cemetery booking submitted!');

    } catch (\Throwable $e) {
        \Log::error('[ERROR] submitBooking failed.', [
            'exception' => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);
        return back()->withErrors('A server error occurred. Please try again or contact support.');
    }
}

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
