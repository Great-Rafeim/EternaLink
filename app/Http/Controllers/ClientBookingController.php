<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\Booking; // ✅ CORRECT
use Barryvdh\DomPDF\Facade\Pdf; // Add this at the top
use Illuminate\Support\Carbon;

class ClientBookingController extends Controller
{
    // Show the booking form for a package
    public function showBookForm($packageId)
    {
        $package = ServicePackage::with(['items.category', 'funeralHome'])
                    ->findOrFail($packageId);

        return view('client.parlors.packages.book', compact('package'));
    }
    
    // Store the booking
    public function store(Request $request, $packageId)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:service_packages,id',
            'funeral_home_id' => 'required|exists:users,id',
            'deceased_name' => 'required|string|max:255',
            'date_of_death' => 'nullable|date',
            'client_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:50',
            'preferred_schedule' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $details = [
            'deceased_name'      => $request->deceased_name,
            'date_of_death'      => $request->date_of_death,
            'client_name'        => $request->client_name,
            'contact_number'     => $request->contact_number,
            'preferred_schedule' => $request->preferred_schedule,
            'notes'              => $request->notes,
        ];

        // Fetch the package to get its cremation/burial status
        $package = ServicePackage::findOrFail($validated['package_id']);

        // Save cremation/burial status to bookings table
        $booking = Booking::create([
            'client_user_id'   => auth()->id(),
            'funeral_home_id'  => $validated['funeral_home_id'],
            'package_id'       => $validated['package_id'],
            'status'           => 'pending',
            'details'          => json_encode($details),
            'is_cremation'     => $package->is_cremation ? 1 : 0, // 1 = cremation, 0 = burial
            // If your table uses 'is_category', change this line to:
            // 'is_category'      => $package->is_cremation ? 1 : 0,
        ]);

        // Notify the funeral home
        $funeralHome = $booking->funeralHome;
        if ($funeralHome) {
            $funeralHome->notify(new \App\Notifications\NewBookingReceived($booking));
        }

        return redirect()->route('client.parlors.index')->with('success', 'Booking submitted!');
    }
}
