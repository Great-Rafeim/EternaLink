<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Partnership;
use App\Models\ResourceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResourceShareController extends Controller
{

    // Show shareable items from partner parlors for a given item
public function showShareableItems(Request $request, $itemId)   
{
    $item = InventoryItem::with('category')->findOrFail($itemId);
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

    // Get all partner shareable items (assets and consumables) **filter status in query**
    $allCandidates = InventoryItem::whereIn('funeral_home_id', $partnerIds)
        ->where('shareable', 1)
        ->with(['funeralUser', 'category'])
        ->where(function ($q) {
            $q->whereHas('category', function ($cat) {
                $cat->where('is_asset', true);
            })->where('status', 'available')
            ->orWhere(function ($q2) {
                $q2->whereHas('category', function ($cat2) {
                    $cat2->where('is_asset', false);
                })
                ->where('shareable_quantity', '>', 0)
                ->where('status', 'available'); // <--- for consumables
            });
        })
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

// Show ALL shareable items from all partner parlors
public function showAllShareableItems()
{
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

    // Get all partner shareable items (assets and consumables) **filter status in query**
    $shareableItems = InventoryItem::whereIn('funeral_home_id', $partnerIds)
        ->where('shareable', 1)
        ->with(['funeralUser', 'category'])
        ->where(function ($q) {
            $q->whereHas('category', function ($cat) {
                $cat->where('is_asset', true);
            })->where('status', 'available')
            ->orWhere(function ($q2) {
                $q2->whereHas('category', function ($cat2) {
                    $cat2->where('is_asset', false);
                })
                ->where('shareable_quantity', '>', 0)
                ->where('status', 'available'); // <--- for consumables
            });
        })
        ->get();

    // No "item" context, so pass null for item
    return view('funeral.partnerships.resource_requests.request', [
        'item' => null,
        'shareableItems' => $shareableItems,
        'search' => null,
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

public function storeRequest(Request $request)
{
    Log::info('storeRequest started', ['request' => $request->all()]);

    try {
        $providerItem = \App\Models\InventoryItem::with('category')->findOrFail($request->input('provider_item_id'));
        Log::debug('Provider Item loaded', ['providerItem' => $providerItem]);

        $isAsset = $providerItem->category && $providerItem->category->is_asset;
        Log::debug('Is Asset?', ['isAsset' => $isAsset]);

        // Dynamic validation rules
        $rules = [
            'requested_item_id' => 'required|exists:inventory_items,id',
            'provider_item_id'  => 'required|exists:inventory_items,id',
            'purpose'           => 'required|string',
            'delivery_method'   => 'required|string|max:100',
            'notes'             => 'nullable|string',
            'contact_name'      => 'required|string|max:255',
            'contact_mobile'    => 'nullable|string|max:30',
            'contact_email'     => 'nullable|email|max:255',
            'location'          => 'nullable|string|max:255',
        ];

if ($isAsset) {
    $rules['reserved_start'] = ['required', 'date', 'after_or_equal:today'];
    $rules['reserved_end']   = [
        'required',
        'date',
        'after:reserved_start',
        function ($attribute, $value, $fail) use ($providerItem, $request) {
            $conflict = \App\Models\AssetReservation::where('inventory_item_id', $providerItem->id)
                ->whereNotIn('status', ['cancelled', 'completed', 'closed'])
                ->where(function($q) use ($request) {
                    $start = $request->input('reserved_start');
                    $end   = $request->input('reserved_end');
                    $q->whereBetween('reserved_start', [$start, $end])
                      ->orWhereBetween('reserved_end', [$start, $end])  
                      ->orWhere(function($sub) use ($start, $end) {
                          $sub->where('reserved_start', '<=', $start)
                              ->where('reserved_end', '>=', $end);
                      });
                })
                ->exists();
            if ($conflict) {
                Log::warning('Asset conflict detected', [
                    'providerItemId' => $providerItem->id,
                    'reserved_start' => $request->input('reserved_start'),
                    'reserved_end'   => $request->input('reserved_end')
                ]);
                $fail('This asset is already reserved during the selected period.');
            }
        }
    ];
}
 else {
            $rules['quantity'] = [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($providerItem) {
                    if ($value > $providerItem->shareable_quantity) {
                        Log::warning('Quantity greater than shareable', ['requested' => $value, 'available' => $providerItem->shareable_quantity]);
                        $fail('The quantity cannot be greater than the provider\'s shareable quantity (' . $providerItem->shareable_quantity . ').');
                    }
                },
            ];
            $rules['preferred_date'] = 'required|date|after_or_equal:today';
        }

        Log::debug('Validation rules built', ['rules' => $rules]);
        $validated = $request->validate($rules);
        Log::info('Validation passed', ['validated' => $validated]);

        $requestedItem = \App\Models\InventoryItem::findOrFail($validated['requested_item_id']);
        Log::debug('Requested Item loaded', ['requestedItem' => $requestedItem]);

        // Prepare data
        $data = [
            'requester_id'       => auth()->id(),
            'provider_id'        => $providerItem->funeral_home_id,
            'requested_item_id'  => $requestedItem->id,
            'provider_item_id'   => $providerItem->id,
            'purpose'            => $validated['purpose'],
            'delivery_method'    => $validated['delivery_method'],
            'notes'              => $validated['notes'] ?? null,
            'contact_name'       => $validated['contact_name'],
            'contact_mobile'     => $validated['contact_mobile'] ?? null,
            'contact_email'      => $validated['contact_email'],
            'location'           => $validated['location'],
            'status'             => 'pending',
        ];

        if ($isAsset) {
            $data['reserved_start'] = $validated['reserved_start'];
            $data['reserved_end']   = $validated['reserved_end'];
            $data['quantity']       = 1; // always 1 for asset
            $data['preferred_date'] = null;
        } else {
            $data['quantity']       = $validated['quantity'];
            $data['preferred_date'] = $validated['preferred_date'];
            $data['reserved_start'] = null;
            $data['reserved_end']   = null;
        }

        Log::debug('Prepared request data', ['data' => $data]);

        // Create the resource request
        $resourceRequest = \App\Models\ResourceRequest::create($data);
        Log::info('ResourceRequest created', ['resourceRequestId' => $resourceRequest->id]);

        // Send notifications to provider and requester
        $providerUser = \App\Models\User::find($resourceRequest->provider_id);
        $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

        if ($providerUser && $providerUser->id != $requesterUser->id) {
            $providerUser->notify(new \App\Notifications\ResourceRequestNotification($resourceRequest->id, true, 'submitted'));
            Log::info('Provider notified', ['providerUserId' => $providerUser->id]);
        }
        if ($requesterUser) {
            $requesterUser->notify(new \App\Notifications\ResourceRequestNotification($resourceRequest->id, false, 'submitted'));
            Log::info('Requester notified', ['requesterUserId' => $requesterUser->id]);
        }

        Log::info('storeRequest completed successfully', ['resourceRequestId' => $resourceRequest->id]);

        return redirect()->route('funeral.partnerships.resource_requests.index')
            ->with('success', 'Resource request sent successfully!');
    } catch (\Exception $e) {
        Log::error('storeRequest error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return redirect()->back()->withInput()->withErrors(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
}


}
