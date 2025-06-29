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
        // NEW: Eager load logs and user
        'serviceLogs.user',
    ]);

    $packageItems = $booking->package->items->map(function($item) {
        return [
            'item'       => $item->name,
            'category'   => $item->category->name ?? '-',
            'brand'      => $item->brand ?? '-',
            'quantity'   => $item->pivot->quantity ?? 1,
            'category_id'=> $item->category->id ?? null,
            'is_asset'   => ($item->category->is_asset ?? false) ? true : false,
        ];
    })->toArray();

    $assetCategories = \DB::table('package_asset_categories')
        ->join('inventory_categories', 'package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
        ->where('package_asset_categories.service_package_id', $booking->package->id)
        ->where('inventory_categories.is_asset', 1)
        ->select('inventory_categories.id', 'inventory_categories.name')
        ->get();

    $assignedAgentIds = \DB::table('booking_agents')
        ->whereNotNull('agent_user_id')
        ->pluck('agent_user_id')
        ->toArray();

    $parlorAgents = \DB::table('users')
        ->join('funeral_home_agent', function ($join) use ($booking) {
            $join->on('users.id', '=', 'funeral_home_agent.agent_user_id')
                ->where('funeral_home_agent.funeral_user_id', $booking->funeral_home_id)
                ->where('funeral_home_agent.status', 'active');
        })
        ->where('users.role', 'agent')
        ->whereNull('users.deleted_at')
        ->whereNotIn('users.id', $assignedAgentIds)
        ->select('users.id', 'users.name', 'users.email')
        ->get();

    $invitationStatus = null;
    $bookingAgent = $booking->bookingAgent;
    if ($bookingAgent && $bookingAgent->client_agent_email) {
        $invitation = \DB::table('agent_client_requests')
            ->where('client_id', $booking->client_user_id)
            ->where('booking_id', $booking->id)
            ->orderByDesc('requested_at')
            ->first();
        $invitationStatus = $invitation ? $invitation->status : null;
    }

    $cemeteryBooking = $booking->cemeteryBooking;

    $useCustomized = $booking->customized_package_id && $booking->customizedPackage;
    $customized = $useCustomized ? $booking->customizedPackage : null;
    $phase2 = $booking->detail;

    // NEW: Get service logs (with user info, sorted oldest first)
    $serviceLogs = $booking->serviceLogs()->with('user')->orderBy('created_at')->get();

    $pdf = Pdf::loadView('client.bookings.pdf', [
        'booking'          => $booking,
        'packageItems'     => $packageItems,
        'assetCategories'  => $assetCategories,
        'parlorAgents'     => $parlorAgents,
        'invitationStatus' => $invitationStatus,
        'bookingAgent'     => $bookingAgent,
        'cemeteryBooking'  => $cemeteryBooking,
        'customized'       => $customized,
        'phase2'           => $phase2,
        // NEW:
        'serviceLogs'      => $serviceLogs,
    ]);

    $filename = 'EternaLink_Booking_' . $booking->id . '.pdf';
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
