<?php

namespace App\Http\Controllers;

use App\Models\Plot;
use App\Models\Cemetery;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\PlotOccupation;
use Illuminate\Support\Facades\Auth;
use App\Models\CemeteryBooking;
use App\Models\BookingDetail;
use Illuminate\Support\Facades\DB;


class PlotController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get the cemetery this user owns
        $cemetery = Cemetery::where('user_id', $user->id)->first();

        if (!$cemetery) {
            return view('cemetery.plots.index', [
                'plots' => collect(),
                'cemeteryMissing' => true,
            ]);
        }

        $query = Plot::where('cemetery_id', $cemetery->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('plot_number', 'like', "%{$search}%");
        }

        $plots = $query->orderBy('section')->orderBy('block')->paginate(15);

        return view('cemetery.plots.index', [
            'plots' => $plots,
            'cemeteryMissing' => false,
        ]);
    }

    public function create()
    {
        return view('cemetery.plots.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $cemetery = Cemetery::where('user_id', $user->id)->first();

        if (!$cemetery) {
            return redirect()->route('cemetery.plots.index')->with('error', 'You do not have a cemetery profile.');
        }

        $validated = $request->validate([
            'plot_number' => 'required|unique:plots,plot_number|max:255',
            'section' => 'nullable|string|max:50',
            'block' => 'nullable|string|max:50', 
            'type' => 'required|in:single,double,family',
        ]);

        $validated['status'] = 'available';
        $validated['cemetery_id'] = $cemetery->id;

        Plot::create($validated);

        return redirect()->route('cemetery.plots.index')->with('success', 'Plot created successfully.');
    }


public function edit(Plot $plot)
{
    $user = Auth::user();
    if ($plot->cemetery->user_id !== $user->id) {
        abort(403, 'Unauthorized');
    }

    // Only load occupation history, no reservations
    $plot->load([
        'occupation',
        'occupationHistory'
    ]);

    // Find a booking detail where this plot is reserved
    $reservedDetail = null;
    if ($plot->status === 'reserved') {
        $reservedDetail = \App\Models\BookingDetail::where('plot_id', $plot->id)->latest()->first();
    }

    return view('cemetery.plots.edit', compact('plot', 'reservedDetail'));
}



    public function update(Request $request, Plot $plot)
    {
        // Only allow updating if the user owns this plot's cemetery
        $user = Auth::user();
        if ($plot->cemetery->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'plot_number' => 'required|max:255|unique:plots,plot_number,' . $plot->id,
            'section' => 'nullable|string|max:50',
            'block' => 'nullable|string|max:50',
            'type' => 'required|in:single,double,family',
            'status' => 'required|in:available,reserved,occupied',
        ]);

        $plot->update($validated);

        return redirect()->route('cemetery.plots.index')->with('success', 'Plot updated successfully.');
    }

    public function destroy(Plot $plot)
    {
        // Only allow deleting if the user owns this plot's cemetery
        $user = Auth::user();
        if ($plot->cemetery->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $plot->delete();

        return redirect()->route('cemetery.plots.index')->with('success', 'Plot #' . $plot->plot_number . ' has been deleted.');
    }

    public function updateReservation(Request $request, Plot $plot)
    {
        // Only allow reservation if the user owns this plot's cemetery
        $user = Auth::user();
        if ($plot->cemetery->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'required|string|max:255',
            'purpose_of_reservation' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'identification_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $plot->update(['status' => 'reserved']);

        Reservation::updateOrCreate(
            ['plot_id' => $plot->id],
            array_merge($validated, ['plot_id' => $plot->id])
        );

        return redirect()->route('cemetery.plots.edit', $plot)->with('success', 'Reservation updated successfully.');
    }

    public function markAvailable(Plot $plot)
    {
        // Only allow if the user owns this plot's cemetery
        $user = Auth::user();
        if ($plot->cemetery->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if ($plot->reservation) {
            $plot->reservation->update(['archived_at' => now()]);
        }

        if ($plot->occupation) {
            $plot->occupation->update(['archived_at' => now()]);
        }

        $plot->update(['status' => 'available']);

        return redirect()->route('cemetery.plots.edit', $plot)->with('success', 'Plot marked as available. Previous records archived.');
    }


////////////////////////
public function createOccupation(Plot $plot, Request $request)
{
    $user = Auth::user();

    // Eager load the cemetery relationship
    $plot->load('cemetery');

    // Defensive null check
    if (!$plot->cemetery) {
        abort(404, 'This plot is not assigned to any cemetery.');
    }
    if ($plot->cemetery->user_id !== $user->id) {
        abort(403, 'Unauthorized');
    }

    // 1. Try to find a BookingDetail record for this plot (reserved via booking)
    $bookingDetail = \App\Models\BookingDetail::where('plot_id', $plot->id)->latest()->first();

    $prefill = [];
    $booking = null;

    if ($bookingDetail) {
        // Convert M/F to Male/Female for select input
        $deceasedSex = match (strtoupper($bookingDetail->deceased_sex ?? '')) {
            'M' => 'Male',
            'F' => 'Female',
            default => null,
        };

        // If you want to pass the booking model for further use (optional)
        $booking = $bookingDetail->booking ?? null; // assuming BookingDetail has booking() relation

        // 3. Prepare prefill values
$prefill = [
    'deceased_first_name'    => $bookingDetail->deceased_first_name,
    'deceased_middle_name'   => $bookingDetail->deceased_middle_name,
    'deceased_last_name'     => $bookingDetail->deceased_last_name,
    'deceased_nickname'      => $bookingDetail->deceased_nickname,
    'deceased_sex'           => $bookingDetail->deceased_sex === 'M' ? 'Male' :
                                 ($bookingDetail->deceased_sex === 'F' ? 'Female' : $bookingDetail->deceased_sex),
    'deceased_birthday'      => $bookingDetail->deceased_birthday,
    'deceased_date_of_death' => $bookingDetail->deceased_date_of_death,
    'deceased_age'           => $bookingDetail->deceased_age,
    'deceased_civil_status'  => $bookingDetail->deceased_civil_status,
    'deceased_residence'     => $bookingDetail->deceased_residence,
    'deceased_citizenship'   => $bookingDetail->deceased_citizenship,
    'remarks'                => '', // always blank for cemetery to fill
    'booking_id'             => $bookingDetail->booking_id,
];
    } else {
        // 4. If not found in booking_details, check for direct booking_id param (for backward compatibility)
        $booking_id = $request->get('booking_id');
        if ($booking_id) {
            $booking = \App\Models\CemeteryBooking::with('details')->find($booking_id);

            if ($booking && $booking->details) {
                $details = $booking->details;

                // Convert M/F to Male/Female for select input
                $deceasedSex = match (strtoupper($details->deceased_sex ?? '')) {
                    'M' => 'Male',
                    'F' => 'Female',
                    default => null,
                };

                $prefill = [
                    'deceased_first_name'    => $details->deceased_first_name ?? null,
                    'deceased_middle_name'   => $details->deceased_middle_name ?? null,
                    'deceased_last_name'     => $details->deceased_last_name ?? null,
                    'deceased_nickname'      => $details->deceased_nickname ?? null,
                    'deceased_sex'           => $deceasedSex,
                    'deceased_birthday'      => $details->deceased_birthday ?? null,
                    'deceased_date_of_death' => $details->deceased_date_of_death ?? null,
                    'deceased_age'           => $details->deceased_age ?? null,
                    'deceased_civil_status'  => $details->deceased_civil_status ?? null,
                    'deceased_residence'     => $details->deceased_residence ?? null,
                    'deceased_citizenship'   => $details->deceased_citizenship ?? null,
                    'remarks'                => '', // always blank for cemetery to fill
                    'booking_id'             => $booking->id,
                ];
            }
        }
    }

    return view('cemetery.plots.occupations.create', [
        'plot'    => $plot,
        'prefill' => $prefill,
        'booking' => $booking,
    ]);
}


/**
 * Store new plot occupation record
 */
public function storeOccupation(Request $request, Plot $plot)
{
    $user = Auth::user();
    if ($plot->cemetery->user_id !== $user->id) {
        abort(403, 'Unauthorized');
    }

    $validated = $request->validate([
        'deceased_first_name' => 'required|string|max:255',
        'deceased_middle_name'=> 'nullable|string|max:255',
        'deceased_last_name'  => 'required|string|max:255',
        'deceased_nickname'   => 'nullable|string|max:255',
        'deceased_sex'        => 'nullable|string|max:10',
        'deceased_birthday'   => 'nullable|date',
        'deceased_date_of_death' => 'nullable|date',
        'deceased_age'        => 'nullable|integer',
        'deceased_civil_status' => 'nullable|string|max:30',
        'deceased_residence'  => 'nullable|string|max:255',
        'deceased_citizenship'=> 'nullable|string|max:255',
        'remarks'             => 'nullable|string',
        'booking_id'          => 'nullable|exists:cemetery_bookings,id',
    ]);

    $validated['plot_id'] = $plot->id;

    DB::transaction(function () use ($plot, $validated) {
        // Update plot status
        $plot->update(['status' => 'occupied']);
        PlotOccupation::create($validated);
    });

    return redirect()->route('cemetery.plots.edit', $plot)
        ->with('success', 'Plot occupation info saved successfully.');
}

/**
 * Show edit form for occupation details
 */
public function editOccupation(Plot $plot, PlotOccupation $occupation)
{
    $user = Auth::user();
    if ($plot->cemetery->user_id !== $user->id) {
        abort(403, 'Unauthorized');
    }
    if ($occupation->plot_id != $plot->id) {
        abort(404);
    }

    return view('cemetery.plots.occupations.edit', [
        'plot' => $plot,
        'occupation' => $occupation,
    ]);
}

/**
 * Update occupation info
 */
public function updateOccupation(Request $request, Plot $plot, PlotOccupation $occupation)
{
    $user = Auth::user();
    if ($plot->cemetery->user_id !== $user->id) {
        abort(403, 'Unauthorized');
    }
    if ($occupation->plot_id != $plot->id) {
        abort(404);
    }

    $validated = $request->validate([
        'deceased_first_name' => 'required|string|max:255',
        'deceased_middle_name'=> 'nullable|string|max:255',
        'deceased_last_name'  => 'required|string|max:255',
        'deceased_nickname'   => 'nullable|string|max:255',
        'deceased_sex'        => 'nullable|string|max:10',
        'deceased_birthday'   => 'nullable|date',
        'deceased_date_of_death' => 'nullable|date',
        'deceased_age'        => 'nullable|integer',
        'deceased_civil_status' => 'nullable|string|max:30',
        'deceased_residence'  => 'nullable|string|max:255',
        'deceased_citizenship'=> 'nullable|string|max:255',
        'remarks'             => 'nullable|string',
        'booking_id'          => 'nullable|exists:cemetery_bookings,id',
    ]);

    $occupation->update($validated);

    return redirect()->route('cemetery.plots.edit', $plot)
        ->with('success', 'Plot occupation updated successfully.');
}

/**
 * Delete occupation record from plot
 */
public function destroyOccupation(Plot $plot, PlotOccupation $occupation)
{
    $user = Auth::user();
    if ($plot->cemetery->user_id !== $user->id) {
        abort(403, 'Unauthorized');
    }
    if ($occupation->plot_id != $plot->id) {
        abort(404);
    }

    $occupation->delete();

    // Optionally, mark plot as available or reserved if no other occupation exists
    if (!$plot->occupation()->exists()) {
        $plot->update(['status' => 'available']);
    }

    return redirect()->route('cemetery.plots.edit', $plot)
        ->with('success', 'Plot occupation removed successfully.');
}

}
