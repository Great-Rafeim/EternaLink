<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\Booking; // ✅ CORRECT


class ClientBookingController extends Controller
{
    // Show the booking form for a package
    public function showBookForm($packageId)
    {
        $package = ServicePackage::with(['items.category', 'funeralHome'])
                    ->findOrFail($packageId);

        return view('client.parlors.packages.book', compact('package'));
    }
    
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

        $booking = Booking::create([
            'client_user_id'   => auth()->id(),
            'funeral_home_id'  => $validated['funeral_home_id'],
            'package_id'       => $validated['package_id'],
            'status'           => 'pending',
            'details'          => json_encode($details),
        ]);

        // Notify the funeral home (User model with 'funeral' role)
        $funeralHome = $booking->funeralHome; // This must be a User model instance

        if ($funeralHome) {
            $funeralHome->notify(new \App\Notifications\NewBookingReceived($booking));
        }

        return redirect()->route('client.parlors.index')->with('success', 'Booking submitted!');
    }





}
