<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResourceRequest;
use App\Models\AssetReservation;



class ResourceRequestController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // Requests sent by this user
        $sentRequests = ResourceRequest::where('requester_id', $userId)
            ->with(['requestedItem', 'providerItem', 'provider'])
            ->latest()
            ->get();

        // Requests where this user is the provider
        $receivedRequests = ResourceRequest::where('provider_id', $userId)
            ->with(['requestedItem', 'providerItem', 'requester'])
            ->latest()
            ->get();

        return view('funeral.partnerships.resource_requests.index', compact('sentRequests', 'receivedRequests'));
    }

    public function reject($id, Request $request)
    {
        $resourceRequest = ResourceRequest::findOrFail($id);

        // Authorization check (optional)
        if ($resourceRequest->provider_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Only pending requests can be rejected
        if ($resourceRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending requests can be rejected.');
        }

        $resourceRequest->status = 'rejected';
        $resourceRequest->save();

        return redirect()->back()->with('success', 'Request rejected successfully.');
    }

public function approve($id, Request $request)
{
    $resourceRequest = ResourceRequest::findOrFail($id);

    if ($resourceRequest->provider_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }

    if ($resourceRequest->status !== 'pending') {
        return redirect()->back()->with('error', 'Only pending requests can be approved.');
    }

    $providerItem = $resourceRequest->providerItem;

    // Check if item is a bookable asset
    if ($providerItem->category && $providerItem->category->is_asset) {
        // Set reservation dates (customize logic as needed)
        $start = \Carbon\Carbon::parse($resourceRequest->preferred_date)->startOfDay();
        $end = (clone $start)->addDays(1); // or more, as per actual arrangement

        // Check for conflicts
        $conflict = \App\Models\AssetReservation::where('inventory_item_id', $providerItem->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('reserved_start', [$start, $end])
                  ->orWhereBetween('reserved_end', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('reserved_start', '<=', $start)
                          ->where('reserved_end', '>=', $end);
                  });
            })
            ->exists();

        if ($conflict) {
            return back()->with('error', "This asset is already reserved during the selected dates.");
        }

        // Create the asset reservation for the partner
        \App\Models\AssetReservation::create([
            'inventory_item_id'        => $providerItem->id,
            'booking_id'               => null,
            'shared_with_partner_id'   => $resourceRequest->requester_id,
            'reserved_start'           => $start,
            'reserved_end'             => $end,
            'status'                   => 'reserved',
            'created_by'               => auth()->id(),
        ]);

        // Mark the asset as reserved
        $providerItem->status = 'reserved';
        $providerItem->save();
    }

    // Otherwise, for consumables/shareables, just approve as before
    $resourceRequest->status = 'approved';
    $resourceRequest->save();

    return redirect()->back()->with('success', 'Request approved successfully.');
}

    public function show($id)
    {
        $request = ResourceRequest::with([
            'requester',
            'provider',
            'requestedItem',
            'providerItem',
        ])->findOrFail($id);

        // Optional: Only allow viewing if you're the requester or provider
        if ($request->requester_id !== auth()->id() && $request->provider_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return view('funeral.partnerships.resource_requests.show', [
            'request' => $request,
        ]);
    }

    public function cancel($id)
    {
        $request = \App\Models\ResourceRequest::findOrFail($id);

        // Only the requester can cancel
        if ($request->requester_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow cancellation if pending
        if ($request->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending requests can be cancelled.');
        }

        $request->status = 'cancelled';
        $request->save();

        return redirect()->back()->with('success', 'Request cancelled successfully.');
    }

public function fulfill($id)
{
    $request = \App\Models\ResourceRequest::findOrFail($id);

    if ($request->requester_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }

    if ($request->status !== 'approved') {
        return back()->with('error', 'Only approved requests can be fulfilled.');
    }

    $providerItem = $request->providerItem;
    $requestedItem = $request->requestedItem;

    // Bookable Asset: close reservation
    if ($providerItem->category && $providerItem->category->is_asset) {
        // Find the reservation
        $reservation = \App\Models\AssetReservation::where('inventory_item_id', $providerItem->id)
            ->where('shared_with_partner_id', $request->requester_id)
            ->where('status', 'reserved')
            ->latest('reserved_start')
            ->first();

        if ($reservation) {
            $reservation->status = 'completed';
            $reservation->save();

            // Mark asset as available
            $providerItem->status = 'available';
            $providerItem->save();
        }
    } else {
        // Consumable logic as before
        if ($providerItem && $requestedItem) {
            $providerItem->shareable_quantity = max(0, $providerItem->shareable_quantity - $request->quantity);
            $providerItem->quantity = max(0, $providerItem->quantity - $request->quantity);

            $providerItem->save();

            $requestedItem->quantity += $request->quantity;
            $requestedItem->save();
        }
    }

    $request->status = 'fulfilled';
    $request->save();

    return redirect()->route('funeral.partnerships.resource_requests.index')
        ->with('success', 'Request marked as fulfilled and inventory updated!');
}




}
