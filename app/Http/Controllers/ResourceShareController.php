<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Partnership;
use App\Models\ResourceRequest;
use Illuminate\Http\Request;

class ResourceShareController extends Controller
{
    // Show shareable items from partner parlors for a given low-stock item
    public function showShareableItems($itemId, Request $request)
    {
        $item = InventoryItem::findOrFail($itemId);
        $userId = auth()->id();

        // Get all accepted partnerships involving this user
        $partnerIds = Partnership::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('partner_id', $userId);
            })
            ->get()
            ->flatMap(function ($partnership) use ($userId) {
                return [$partnership->requester_id == $userId ? $partnership->partner_id : $partnership->requester_id];
            })
            ->unique()
            ->values()
            ->all();

        // Search term (auto-filled with item name or user input)
        $search = $request->input('search', $item->name);

        // Get all partner shareable items with available quantity
        $allCandidates = InventoryItem::whereIn('funeral_home_id', $partnerIds)
            ->where('shareable', 1)
            ->where('shareable_quantity', '>', 0)
            ->with('funeralUser')
            ->get();

        // Fuzzy filter: show items with similar name or brand
        $maxDistance = 3;
        $shareableItems = $allCandidates->filter(function ($candidate) use ($search, $maxDistance) {
            $distance = levenshtein(strtolower($candidate->name), strtolower($search));
            if ($distance <= $maxDistance) return true;

            if ($candidate->brand) {
                $brandDistance = levenshtein(strtolower($candidate->brand), strtolower($search));
                if ($brandDistance <= $maxDistance) return true;
            }
            return false;
        });

        return view('funeral.partnerships.resource_requests.request', [
            'item' => $item,
            'shareableItems' => $shareableItems,
            'search' => $search,
        ]);
    }

    // Show the request form for a selected shareable item
    public function createRequestForm($requestedId, $providerId)
    {
        $requestedItem = InventoryItem::with('funeralUser', 'category')->findOrFail($requestedId);
        $providerItem = InventoryItem::with('funeralUser', 'category')->findOrFail($providerId);
        $user = auth()->user();

        return view('funeral.partnerships.resource_requests.request_form', [
            'requestedItem' => $requestedItem,
            'providerItem' => $providerItem,
            'user' => $user,
        ]);
    }

    // Store the resource request
    public function storeRequest(Request $request)
    {
        $providerItem = InventoryItem::findOrFail($request->input('provider_item_id'));

        $validated = $request->validate([
            'requested_item_id' => 'required|exists:inventory_items,id',
            'provider_item_id' => 'required|exists:inventory_items,id',
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($providerItem) {
                    if ($value > $providerItem->shareable_quantity) {
                        $fail('The quantity cannot be greater than the provider\'s shareable quantity (' . $providerItem->shareable_quantity . ').');
                    }
                },
            ],
            'purpose' => 'required|string',
            'preferred_date' => 'required|date|after_or_equal:today',
            'delivery_method' => 'required|string|max:100',
            'notes' => 'nullable|string',
            'contact_name' => 'required|string|max:255',
            'contact_mobile' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $requestedItem = InventoryItem::findOrFail($validated['requested_item_id']);

        ResourceRequest::create([
            'requester_id' => auth()->id(),
            'provider_id' => $providerItem->funeral_home_id,
            'requested_item_id' => $requestedItem->id,
            'provider_item_id' => $providerItem->id,
            'quantity' => $validated['quantity'],
            'purpose' => $validated['purpose'],
            'preferred_date' => $validated['preferred_date'],
            'delivery_method' => $validated['delivery_method'],
            'notes' => $validated['notes'],
            'contact_name' => $validated['contact_name'],
            'contact_mobile' => $validated['contact_mobile'],
            'contact_email' => $validated['contact_email'],
            'location' => $validated['location'],
            'status' => 'pending',
        ]);

        // Redirect to the resource requests index for the user
        return redirect()->route('funeral.partnerships.resource_requests.index')
            ->with('success', 'Resource request sent successfully!');
    }
}
