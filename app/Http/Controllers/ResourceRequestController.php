<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResourceRequest;


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

        // Make sure only the correct user can fulfill
        if ($request->requester_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Only allow if currently approved
        if ($request->status !== 'approved') {
            return back()->with('error', 'Only approved requests can be fulfilled.');
        }

        // Update provider’s shareable_quantity and requested item’s quantity
        $providerItem = $request->providerItem;
        $requestedItem = $request->requestedItem;

        if ($providerItem && $requestedItem) {
            $providerItem->shareable_quantity = max(0, $providerItem->shareable_quantity - $request->quantity);
            $providerItem->save();

            $requestedItem->quantity += $request->quantity;
            $requestedItem->save();
        }

        $request->status = 'fulfilled';
        $request->save();

        return redirect()->route('funeral.partnerships.resource_requests.index')
            ->with('success', 'Request marked as fulfilled and inventory updated!');
    }



}
