<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingServiceLog;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\AssetReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingServiceUpdated;
use App\Notifications\BookingServiceEnded;
use App\Notifications\BookingCompletedNotification;


class ManageServiceController extends Controller
{
    /**
     * Show the Manage Service page for a booking.
     */
public function index($bookingId)
{
    $booking = Booking::with([
        'client',
        'agent',
        'package',
        'package.components',
    ])->findOrFail($bookingId);

    // Asset categories for this package
    $packageAssetCategoryIds = \App\Models\PackageAssetCategory::where('service_package_id', $booking->package_id)
        ->pluck('inventory_category_id');

    $assetCategories = InventoryCategory::whereIn('id', $packageAssetCategoryIds)
        ->where('is_asset', 1)
        ->get();

    // Assigned assets: get currently assigned inventory items for this booking/category
    $assignedAssets = [];
    foreach ($assetCategories as $cat) {
        $reservation = AssetReservation::where('booking_id', $booking->id)
            ->whereHas('inventoryItem', fn($q) => $q->where('inventory_category_id', $cat->id))
            ->whereIn('status', ['reserved', 'in_use'])
            ->with('inventoryItem')
            ->latest()
            ->first();
        $assignedAssets[$cat->id] = $reservation ? $reservation->inventoryItem : null;
    }

    // Service window for asset reservation
    $details = $booking->detail;
    $start = $details?->wake_start_date ?? $details?->interment_cremation_date;
    $end   = $details?->interment_cremation_date ?? $details?->wake_end_date ?? $start;

    // Build available assets (status 'available' or 'borrowed_from_partner', not overlapping with another booking)
    $availableAssets = [];
    foreach ($assetCategories as $cat) {
        $assets = InventoryItem::where('inventory_category_id', $cat->id)
            ->where('funeral_home_id', $booking->funeral_home_id)
            ->whereIn('status', ['available', 'borrowed_from_partner'])
            ->whereNotIn('id', function ($query) use ($start, $end) {
                $query->select('inventory_item_id')
                    ->from('asset_reservations')
                    ->where(function ($q) use ($start, $end) {
                        $q->where('reserved_start', '<', $end)
                          ->where('reserved_end', '>', $start);
                    })
                    ->whereIn('status', ['reserved', 'in_use']);
            })
            ->get();
        $availableAssets[$cat->id] = $assets;
    }

    $serviceLogs = BookingServiceLog::with('user')
        ->where('booking_id', $booking->id)
        ->orderBy('created_at')
        ->get();

    return view('funeral.bookings.manage-service', [
        'booking'         => $booking,
        'serviceLogs'     => $serviceLogs,
        'assetCategories' => $assetCategories,
        'availableAssets' => $availableAssets,
        'assignedAssets'  => $assignedAssets,
        'serviceStart'    => $start,
        'serviceEnd'      => $end,
    ]);
}

    /**
     * Handle asset assignments for this booking.
     */
public function assignAssets(Request $request, $bookingId)
{
    $booking = Booking::findOrFail($bookingId);
    $details = $booking->detail;
    $assets = $request->input('assets', []);

    // Get all relevant asset categories for the package
    $packageAssetCategoryIds = \App\Models\PackageAssetCategory::where('service_package_id', $booking->package_id)
        ->pluck('inventory_category_id');
    $assetCategories = \App\Models\InventoryCategory::whereIn('id', $packageAssetCategoryIds)->get()->keyBy('id');

    \Log::debug("Assigning assets to booking", [
        'booking_id' => $booking->id,
        'assets_input' => $assets,
        'assetCategories' => $assetCategories->keys()->all(),
    ]);

    DB::transaction(function () use ($booking, $assets, $details, $assetCategories) {
        foreach ($assets as $categoryId => $itemId) {
            \Log::debug("Processing category", [
                'categoryId' => $categoryId,
                'itemId' => $itemId,
            ]);
            if (!$itemId) {
                \Log::debug("No asset selected for category, skipping", ['categoryId' => $categoryId]);
                continue;
            }

            $category = $assetCategories[$categoryId] ?? null;

            // Default date range
            $start = $details?->wake_start_date ?? $details?->interment_cremation_date;
            $end   = $details?->interment_cremation_date ?? $details?->wake_end_date ?? $start;

            // If single_event reservation_mode: use interment_cremation_date for both
            if ($category && $category->is_asset && $category->reservation_mode === 'single_event') {
                $start = $end = $details?->interment_cremation_date;
                \Log::debug("Single event mode", ['categoryId' => $categoryId, 'start' => $start, 'end' => $end]);
            }

            if (!$start || !$end) {
                \Log::debug("Start or end date missing, skipping", ['categoryId' => $categoryId]);
                continue;
            }

            // Close previous reservations for this booking/category
            $closedCount = \App\Models\AssetReservation::where('booking_id', $booking->id)
                ->whereHas('inventoryItem', function ($q) use ($categoryId) {
                    $q->where('inventory_category_id', $categoryId);
                })
                ->whereIn('status', ['reserved', 'in_use'])
                ->update(['status' => 'closed']);

            \Log::debug("Closed previous reservations", [
                'categoryId' => $categoryId,
                'closedCount' => $closedCount,
            ]);

            // Check item status
            $item = \App\Models\InventoryItem::find($itemId);
            \Log::debug("Fetched inventory item", [
                'itemId' => $itemId,
                'status' => $item ? $item->status : null,
            ]);

            // --- LOGIC for borrowed_from_partner ---
            if ($item && $item->status === 'borrowed_from_partner') {
                $borrowedStart = $item->borrowed_start ? \Carbon\Carbon::parse($item->borrowed_start) : null;
                $borrowedEnd = $item->borrowed_end ? \Carbon\Carbon::parse($item->borrowed_end) : null;
                $assignStart = \Carbon\Carbon::parse($start . ' 00:00:00');
                $assignEnd = \Carbon\Carbon::parse($end . ' 23:59:59');

                \Log::debug("Handling borrowed_from_partner item", [
                    'borrowedStart' => $borrowedStart,
                    'borrowedEnd' => $borrowedEnd,
                    'assignStart' => $assignStart,
                    'assignEnd' => $assignEnd,
                ]);

                if (
                    $borrowedStart &&
                    $borrowedEnd &&
                    $assignStart->greaterThanOrEqualTo($borrowedStart) &&
                    $assignEnd->lessThanOrEqualTo($borrowedEnd)
                ) {
                    // Find the resource request via borrowed_reservation_id (if present)
                    $resourceRequestId = null;
                    if ($item->borrowed_reservation_id) {
                        $borrowedReservation = \App\Models\AssetReservation::find($item->borrowed_reservation_id);
                        $resourceRequestId = $borrowedReservation?->resource_request_id;
                    }

                    \Log::debug("Borrowed resource_request_id resolved", [
                        'itemId' => $itemId,
                        'resource_request_id' => $resourceRequestId,
                        'borrowed_reservation_id' => $item->borrowed_reservation_id,
                    ]);

                    // Create reservation for borrowed asset
                    $reservation = \App\Models\AssetReservation::create([
                        'inventory_item_id'    => $itemId,
                        'booking_id'           => $booking->id,
                        'reserved_start'       => $assignStart,
                        'reserved_end'         => $assignEnd,
                        'status'               => 'reserved',
                        'created_by'           => auth()->id(),
                        'resource_request_id'  => $resourceRequestId,
                    ]);
                    \Log::debug("Created reservation for borrowed asset", [
                        'reservation_id' => $reservation->id,
                        'itemId' => $itemId,
                        'resource_request_id' => $resourceRequestId,
                    ]);
                } else {
                    \Log::debug("Booking period not within borrowed window. Skipping asset.", [
                        'categoryId' => $categoryId,
                        'itemId' => $itemId,
                        'borrowedStart' => $borrowedStart,
                        'borrowedEnd' => $borrowedEnd,
                        'assignStart' => $assignStart,
                        'assignEnd' => $assignEnd,
                    ]);
                    continue;
                }
            }
            // --- END borrowed_from_partner logic ---

            // For "available" assets (normal flow)
            elseif ($item && $item->status === 'available') {
                $item->status = 'reserved';
                $item->save();
                \Log::debug("Updated item status to reserved", ['itemId' => $itemId]);

                $reservation = \App\Models\AssetReservation::create([
                    'inventory_item_id' => $itemId,
                    'booking_id'        => $booking->id,
                    'reserved_start'    => $start . ' 00:00:00',
                    'reserved_end'      => $end . ' 23:59:59',
                    'status'            => 'reserved',
                    'created_by'        => auth()->id(),
                ]);
                \Log::debug("Created reservation for available asset", [
                    'reservation_id' => $reservation->id,
                    'itemId' => $itemId,
                ]);
            } else {
                \Log::debug("Item is not assignable due to its status", [
                    'itemId' => $itemId,
                    'status' => $item ? $item->status : null,
                ]);
            }
            // Optionally, skip any other statuses (e.g., maintenance, shared_to_partner)
        }
    });

    return back()->with('success', 'Assets assigned and reserved successfully.');
}






    /**
     * Store a new service log.
     */
    public function postUpdate(Request $request, $bookingId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $booking = Booking::findOrFail($bookingId);

        // Only allow if ongoing
        if ($booking->status !== Booking::STATUS_ONGOING) {
            return back()->with('error', 'You can only post updates while the service is ongoing.');
        }

        BookingServiceLog::create([
            'booking_id' => $booking->id,
            'user_id'    => auth()->id(),
            'message'    => $request->message,
        ]);

        // Notify client and agent
        $msg = "Update on your funeral service: " . $request->message;
        if ($booking->client) $booking->client->notify(new BookingServiceUpdated($booking, $msg));
        if ($booking->agent) $booking->agent->notify(new BookingServiceUpdated($booking, $msg));

        return back()->with('success', 'Service update posted and client notified.');
    }

    /**
     * End the service and update booking status.
     */
    public function endService(Request $request, $bookingId)
    {
        $user = $request->user();
        $booking = Booking::with([
            'assetReservations.inventoryItem',
            'client',
            'funeralHome',
            'agent',
        ])->findOrFail($bookingId);

        // Only allow if not already completed
        if ($booking->status === Booking::STATUS_COMPLETED) {
            return back()->with('warning', 'Service has already been ended.');
        }

        DB::transaction(function () use ($booking, $user) {
            // 1. Close all asset reservations, update inventory items
            foreach ($booking->assetReservations as $reservation) {
                $reservation->status = 'closed';
                $reservation->save();

                $item = $reservation->inventoryItem;
                if ($item) {
                    // Only update if NOT borrowed/shared partner
                    if (
                        $item->status !== 'borrowed_from_partner' &&
                        $item->status !== 'shared_to_partner'
                    ) {
                        $item->status = 'available';
                        $item->save();
                    }
                }
            }

            // 2. Update booking status
            $booking->status = Booking::STATUS_COMPLETED;
            $booking->save();

            // 3. Log service closure
            BookingServiceLog::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'message' => "Service ended by {$user->name} on " . now()->format('Y-m-d H:i'),
            ]);

            // 4. Send notifications (client, funeralHome, agent)
            $notifiables = collect([$booking->client, $booking->funeralHome, $booking->agent])
                ->filter(); // Removes nulls
            Notification::send($notifiables, new BookingCompletedNotification($booking));
        });

        return redirect()->back()->with('success', 'Service successfully ended and assets released.');
    }
}
