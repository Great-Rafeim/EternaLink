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
    /**
     * Display a listing of bookings for this cemetery.
     */
    public function index(Request $request)
    {
        $statuses = ['pending', 'approved', 'rejected'];
        $status = $request->query('status', '');

        $query = CemeteryBooking::with(['user', 'cemetery.user']);

        if ($status && in_array($status, $statuses)) {
            $query->where('status', $status);
        }

        $bookings = $query->orderByDesc('created_at')->paginate(15);

        return view('cemetery.bookings.index', compact('bookings', 'statuses', 'status'));
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

    // Only get plots for THIS cemetery user where status is available
    $availablePlots = \App\Models\Plot::where('cemetery_id', Auth::id())
        ->where('status', 'available')
        ->get();

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
        $cemeteryBooking = CemeteryBooking::with(['funeralBooking'])->findOrFail($id);

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

        // Notifications
        // Get Client, Funeral Parlor, Agent
        $client = User::find($booking->client_user_id);
        $funeralParlor = User::find($booking->funeral_home_id);

        // Find agent via booking_agents table (assuming one agent per booking)
        $agentUserId = DB::table('booking_agents')
            ->where('booking_id', $booking->id)
            ->value('agent_user_id');
        $agent = $agentUserId ? User::find($agentUserId) : null;

        // Prepare notification data
        $notifData = [
            'title' => 'Cemetery Booking Approved',
            'message' => 'Your cemetery booking has been approved and a plot has been assigned.',
            'booking_id' => $cemeteryBooking->id,
            'plot_number' => $plot->plot_number,
        ];

        // Notify client, funeral parlor, agent (if found)
        if ($client) {
            $client->notify(new \App\Notifications\CemeteryBookingApproved($notifData));
        }
        if ($funeralParlor) {
            $funeralParlor->notify(new \App\Notifications\CemeteryBookingApproved($notifData));
        }
        if ($agent) {
            $agent->notify(new \App\Notifications\CemeteryBookingApproved($notifData));
        }
    });

    return redirect()->route('cemetery.bookings.index')->with('success', 'Booking approved and plot assigned.');
}

}
