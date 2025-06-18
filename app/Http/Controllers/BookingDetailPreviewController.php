<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf; 

class BookingDetailPreviewController extends Controller
{
    /**
     * Display the full booking details (phase 1 and phase 2) for client.
     * Route: GET /client/bookings/{booking}/details
     */
    public function show($bookingId)
    {
        // Load booking with all necessary relationships
        $booking = Booking::with([
            'client',
            'funeralHome',
            'package.items.category',
            'agent',
            'customizedPackage.items.inventoryItem.category',
            'detail',
        ])->findOrFail($bookingId);

        // Authorize (only client who owns this booking can view)
        if ($booking->client_user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Decide which package to use (customized or default)
        $useCustomized = $booking->customized_package_id && $booking->customizedPackage;
        $customized = $useCustomized ? $booking->customizedPackage : null;
        $customItems = $customized ? $customized->items : collect();

        $phase2 = $booking->detail;

        return view('client.bookings.show', [
            'booking'      => $booking,
            'customized'   => $customized,
            'customItems'  => $customItems,
            'phase2'       => $phase2,
        ]);
    }

    /**
     * Export full booking details as PDF.
     * Route: GET /client/bookings/{booking}/details/exportPdf
     */
    public function exportPdf($bookingId)
    {
        $booking = Booking::with([
            'client',
            'funeralHome',
            'package.items.category',
            'agent',
            'customizedPackage.items.inventoryItem.category',
            'detail',
        ])->findOrFail($bookingId);

        if ($booking->client_user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $useCustomized = $booking->customized_package_id && $booking->customizedPackage;
        $customized = $useCustomized ? $booking->customizedPackage : null;

        $pdf = Pdf::loadView('client.bookings.pdf', [
            'booking'    => $booking,
            'customized' => $customized,
            'phase2'     => $booking->detail,
        ]);

        $filename = 'EternaLink_Booking_' . $booking->id . '.pdf';
        return $pdf->download($filename);
    }
}
