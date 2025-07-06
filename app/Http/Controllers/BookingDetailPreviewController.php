<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf; 
use Carbon\Carbon;

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
public function exportPdf(Booking $booking)
{
    $this->authorizeBooking($booking);

    $booking->load([
        'package.items.category',
        'client',
        'funeralHome',
        'agent',
        'bookingAgent.agentUser',
        'cemeteryBooking' => function ($q) {
            $q->where('status', 'approved')
              ->with(['cemetery.user', 'plot']);
        },
        // Eager-load for customized package flows:
        'customizedPackage.items.inventoryItem.category',
        'customizedPackage.items.substituteFor',
        'serviceLogs.user',
    ]);

    // Fetch asset categories WITH price, for consistency
    $assetCategories = \DB::table('inventory_categories')
        ->join('package_asset_categories', function ($join) use ($booking) {
            $join->on('package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
                ->where('package_asset_categories.service_package_id', $booking->package->id);
        })
        ->where('inventory_categories.is_asset', 1)
        ->select(
            'inventory_categories.id as id',
            'inventory_categories.name as name',
            'inventory_categories.is_asset',
            'package_asset_categories.price as price'
        )
        ->get();

    $assetCategoryPrices = $assetCategories->pluck('price', 'id')->toArray();

    // Service logs sorted oldest first
    $serviceLogs = $booking->serviceLogs()->with('user')->orderBy('created_at')->get();

    // Find assigned plot via booking_details
    $bookingDetail = \App\Models\BookingDetail::where('booking_id', $booking->id)->first();
    $plot = null;
    $plotCemetery = null;
    $cemeteryOwner = null;

    if ($bookingDetail && $bookingDetail->plot_id) {
        $plot = \App\Models\Plot::with('cemetery.user')->find($bookingDetail->plot_id);
        if ($plot) {
            $plotCemetery = $plot->cemetery;
            $cemeteryOwner = $plotCemetery?->user;
        }
    }

    // Customization/package logic for PDF
    $useCustomized = $booking->customized_package_id && $booking->customizedPackage;
    $customized = $useCustomized ? $booking->customizedPackage : null;

    // This lets you access the same logic as in the show view
    $phase2 = $booking->detail;

    // Optional: pass details (decoded JSON if you use it in the view)
    $details = $booking->decoded_details ?? null;

    $pdf = Pdf::loadView('client.bookings.pdf', [
        'booking'             => $booking,
        'assetCategories'     => $assetCategories,
        'assetCategoryPrices' => $assetCategoryPrices,
        'customized'          => $customized,
        'phase2'              => $phase2,
        'serviceLogs'         => $serviceLogs,
        'plot'                => $plot,
        'plotCemetery'        => $plotCemetery,
        'cemeteryOwner'       => $cemeteryOwner,
        'details'             => $details,
    ]);

    $filename = 'EternaLink_Booking_' . $booking->id . '.pdf';
    return $pdf->download($filename);
}

public function downloadCertificate(\App\Models\Booking $booking)
{
    $this->authorizeBooking($booking); // Use centralized access logic

    // Check if certificate is released
    if (!$booking->certificate_released_at || !$booking->certificate_signature) {
        return back()->with('error', 'Certificate not available yet.');
    }

    $booking->loadMissing('funeralHome.funeralParlor');

    $parlor = $booking->funeralHome->funeralParlor ?? null;

    // Build certificate details
    $details = is_array($booking->details) ? $booking->details : json_decode($booking->details, true) ?? [];
    $deceasedName = $details['deceased_name'] ?? '—';
    $dateOfDeath  = !empty($details['date_of_death']) ? \Carbon\Carbon::parse($details['date_of_death'])->format('F d, Y') : '—';
    $cremationDate = !empty($details['cremation_date'])
        ? \Carbon\Carbon::parse($details['cremation_date'])->format('F d, Y')
        : (!empty($details['preferred_schedule']) 
            ? \Carbon\Carbon::parse($details['preferred_schedule'])->format('F d, Y') 
            : ($booking->created_at ? $booking->created_at->format('F d, Y') : '—'));
    $issuedDate = $booking->certificate_released_at ? \Carbon\Carbon::parse($booking->certificate_released_at)->format('F d, Y') : now()->format('F d, Y');
    $funeralParlorName = $parlor?->name ?? $booking->funeralHome?->name ?? '—';
    $ownerName = $parlor?->owner_name ?? $parlor?->contact_person ?? 'Funeral Owner';

    // Signature image: build absolute path or base64
    $signatureImage = null;
    if ($booking->certificate_signature) {
        if (str_starts_with($booking->certificate_signature, 'data:image')) {
            $signatureImage = $booking->certificate_signature;
        } else {
            $signaturePath = storage_path('app/public/' . $booking->certificate_signature);
            if (file_exists($signaturePath)) {
                $signatureImage = 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath));
            }
        }
    }

    $pdf = \PDF::loadView('client.certificates.cremation_certificate', [
        'deceasedName'      => $deceasedName,
        'dateOfDeath'       => $dateOfDeath,
        'cremationDate'     => $cremationDate,
        'issuedDate'        => $issuedDate,
        'funeralParlorName' => $funeralParlorName,
        'ownerName'         => $ownerName,
        'signatureImage'    => $signatureImage,
        'booking'           => $booking, // Optional: pass if needed
    ])->setPaper('A4', 'landscape'); // LANDSCAPE!

    $filename = 'Cremation-Certificate-' . str_replace(' ', '-', $deceasedName) . '-' . $booking->id . '.pdf';

    return $pdf->download($filename);
}



protected function authorizeBooking(\App\Models\Booking $booking)
{
    $user = auth()->user();

    // Admins can always access
    if ($user->role === 'admin') {
        return;
    }

    // Funeral parlor associated with booking
    if ($booking->funeral_home_id == $user->id && $user->role === 'funeral') {
        return;
    }

    // Client who owns the booking
    if ($booking->client_user_id == $user->id && $user->role === 'client') {
        return;
    }

    // Agent assigned to the booking (direct agent assignment)
    if (
        ($booking->agent_id && $booking->agent_id == $user->id && $user->role === 'agent')
        // or check bookingAgent table if you use a pivot
        || (method_exists($booking, 'bookingAgent') && $booking->bookingAgent && $booking->bookingAgent->agent_user_id == $user->id)
    ) {
        return;
    }

    // Cemetery staff (if applicable to your business logic)
    if (property_exists($booking, 'cemeteryBooking') && $booking->cemeteryBooking && $booking->cemeteryBooking->cemetery_user_id == $user->id && $user->role === 'cemetery') {
        return;
    }

    // Not authorized
    abort(403, 'Unauthorized.');
}

}
