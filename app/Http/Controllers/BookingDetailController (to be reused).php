<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\BookingDetail;

class BookingDetailPreviewController extends Controller
{
    // Show the create details form
    public function create($bookingId)
    {
        $booking = Booking::with('detail')->findOrFail($bookingId);

        // Only allow if this client owns the booking and it's confirmed
        if ($booking->client_user_id !== auth()->id() || $booking->status !== 'confirmed') {
            abort(403, 'Unauthorized or booking not ready for details.');
        }

        // If already filled, redirect to view
        if ($booking->detail) {
            return redirect()->route('client.bookings.details.show', $booking->id);
        }

        return view('client.bookings.details.create', compact('booking'));
    }

    // Store the submitted details
    public function store(Request $request, $bookingId)
    {
        $booking = Booking::with('detail')->findOrFail($bookingId);

        // Only allow if this client owns the booking and it's confirmed
        if ($booking->client_user_id !== auth()->id() || $booking->status !== 'confirmed') {
            abort(403, 'Unauthorized or booking not ready for details.');
        }

        // Validate fields (you can further tighten/expand rules)
        $validated = $request->validate([
            'deceased_first_name' => 'required|string|max:255',
            'deceased_middle_name' => 'nullable|string|max:255',
            'deceased_last_name' => 'required|string|max:255',
            'deceased_nickname' => 'nullable|string|max:255',
            'deceased_residence' => 'nullable|string|max:500',
            'deceased_sex' => 'nullable|string|max:10',
            'deceased_civil_status' => 'nullable|string|max:30',
            'deceased_birthday' => 'nullable|date',
            'deceased_age' => 'nullable|integer',
            'deceased_date_of_death' => 'nullable|date',
            'deceased_religion' => 'nullable|string|max:255',
            'deceased_occupation' => 'nullable|string|max:255',
            'deceased_citizenship' => 'nullable|string|max:255',
            'deceased_time_of_death' => 'nullable|string|max:50',
            'deceased_cause_of_death' => 'nullable|string|max:255',
            'deceased_place_of_death' => 'nullable|string|max:255',

            // Father's name
            'deceased_father_first_name' => 'nullable|string|max:255',
            'deceased_father_middle_name' => 'nullable|string|max:255',
            'deceased_father_last_name' => 'nullable|string|max:255',

            // Mother's maiden name
            'deceased_mother_first_name' => 'nullable|string|max:255',
            'deceased_mother_middle_name' => 'nullable|string|max:255',
            'deceased_mother_last_name' => 'nullable|string|max:255',

            // Corpse Disposal/Interment
            'corpse_disposal' => 'nullable|string|max:255',
            'interment_cremation_date' => 'nullable|date',
            'interment_cremation_time' => 'nullable|string|max:50',
            'cemetery_or_crematory' => 'nullable|string|max:255',

            // Documents and Release
            'death_cert_registration_no' => 'nullable|string|max:255',
            'death_cert_released_to' => 'nullable|string|max:255',
            'death_cert_released_date' => 'nullable|date',

            'funeral_contract_no' => 'nullable|string|max:255',
            'funeral_contract_released_to' => 'nullable|string|max:255',
            'funeral_contract_released_date' => 'nullable|date',

            'official_receipt_no' => 'nullable|string|max:255',
            'official_receipt_released_to' => 'nullable|string|max:255',
            'official_receipt_released_date' => 'nullable|date',

            // Informant
            'informant_name' => 'nullable|string|max:255',
            'informant_age' => 'nullable|integer',
            'informant_civil_status' => 'nullable|string|max:50',
            'informant_relationship' => 'nullable|string|max:255',
            'informant_contact_no' => 'nullable|string|max:100',
            'informant_address' => 'nullable|string|max:500',

            // Service/Payment/Remarks
            'service' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'other_fee' => 'nullable|string|max:255',
            'deposit' => 'nullable|string|max:255',
            'cswd' => 'nullable|string|max:255',
            'dswd' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',

            // Attestation
            'certifier_name' => 'nullable|string|max:255',
            'certifier_relationship' => 'nullable|string|max:255',
            'certifier_residence' => 'nullable|string|max:500',
            'certifier_amount' => 'nullable|numeric',
            'certifier_signature' => 'nullable|string|max:255',
        ]);

        // Store details
        $bookingDetail = new BookingDetail($validated);
        $bookingDetail->booking_id = $booking->id;
        $bookingDetail->save();

        // Set booking as ongoing
        $booking->status = 'ongoing';
        $booking->save();

        // Redirect to document upload or details view
        return redirect()->route('client.bookings.details.show', $booking->id)
            ->with('success', 'Booking details submitted. Please upload required documents.');
    }

    // Show read-only details
    public function show($bookingId)
    {
        $booking = Booking::with('detail')->findOrFail($bookingId);

        if ($booking->client_user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $detail = $booking->detail;

        if (!$detail) {
            return redirect()->route('client.bookings.details.create', $bookingId)
                ->with('error', 'You need to complete the details first.');
        }

        return view('client.bookings.details.show', compact('booking', 'detail'));
    }

    public function pdf($bookingId)
    {
        $booking = \App\Models\Booking::with('detail')->findOrFail($bookingId);

        // Authorize
        if ($booking->client_user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $detail = $booking->detail;

        if (!$detail) {
            return redirect()->route('client.bookings.details.create', $bookingId)
                ->with('error', 'You need to complete the details first.');
        }

        $pdf = Pdf::loadView('client.bookings.details.pdf', compact('booking', 'detail'))
                ->setPaper('A4', 'portrait');

        $filename = 'Booking-Details-'.$booking->id.'.pdf';

        return $pdf->download($filename);
    }
}
