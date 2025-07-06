<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\Partnership;
use App\Models\ResourceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResourceShareController extends Controller
{

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
                ->where('status', 'available');
            });
        })
        ->get();

    // Improved loose/fuzzy filter
    $maxDistance = 3;
    $searchLower = strtolower($search);
    $searchWords = preg_split('/[\s\-_]+/', $searchLower); // Split on spaces, hyphens, underscores

    $shareableItems = $allCandidates->filter(function ($candidate) use ($searchLower, $searchWords, $maxDistance) {
        $nameLower = strtolower($candidate->name);
        $brandLower = strtolower($candidate->brand ?? '');

        // 1. Exact or partial substring match
        if (stripos($nameLower, $searchLower) !== false) return true;
        if ($brandLower && stripos($brandLower, $searchLower) !== false) return true;

        // 2. Any search word is in name/brand
        foreach ($searchWords as $word) {
            if (strlen($word) < 2) continue; // skip 1-letter noise
            if (stripos($nameLower, $word) !== false) return true;
            if ($brandLower && stripos($brandLower, $word) !== false) return true;
        }

        // 3. Fuzzy levenshtein (allow typos)
        if (levenshtein($nameLower, $searchLower) <= $maxDistance) return true;
        if ($brandLower && levenshtein($brandLower, $searchLower) <= $maxDistance) return true;

        // 4. Word-by-word fuzzy match
        $nameWords = preg_split('/[\s\-_]+/', $nameLower);
        foreach ($searchWords as $sw) {
            foreach ($nameWords as $nw) {
                if (strlen($sw) > 2 && levenshtein($sw, $nw) <= 1) return true;
            }
        }

        return false;
    });

    return view('funeral.partnerships.resource_requests.request', [
        'item' => $item,
        'shareableItems' => $shareableItems,
        'search' => $search,
    ]);
}


public function showAllShareableItems(Request $request)
{
    $userId = auth()->id();
    $search = $request->input('search'); // Get the search input

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
    $shareableItemsQuery = InventoryItem::whereIn('funeral_home_id', $partnerIds)
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
                ->where('status', 'available');
            });
        });

    // Add search filter if needed
    if ($search) {
        $shareableItemsQuery->where(function($query) use ($search) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('brand', 'like', "%$search%");
        });
    }

    $shareableItems = $shareableItemsQuery->get();

    return view('funeral.partnerships.resource_requests.request', [
        'item' => null,
        'shareableItems' => $shareableItems,
        'search' => $search,
    ]);
}


public function createRequestForm($arg1 = null, $arg2 = null)
{
    $user = auth()->user();

    \Log::debug('[createRequestForm] Called', [
        'arg1' => $arg1,
        'arg2' => $arg2,
        'user_id' => $user ? $user->id : null,
    ]);

    // Route-agnostic: Figure out which param is which
    if ($arg2 === null) {
        // Only provider passed
        $providerId = $arg1;
        $requestedId = null;
        \Log::debug('[createRequestForm] Mode: provider-only', [
            'providerId' => $providerId,
        ]);
    } else {
        // Both requested and provider passed
        $requestedId = $arg1;
        $providerId = $arg2;
        \Log::debug('[createRequestForm] Mode: requested+provider', [
            'requestedId' => $requestedId,
            'providerId' => $providerId,
        ]);
    }

    // Find provider item
    try {
        $providerItem = \App\Models\InventoryItem::with('funeralUser', 'category')->findOrFail($providerId);
        \Log::debug('[createRequestForm] providerItem found', ['providerItem' => $providerItem->toArray()]);
    } catch (\Exception $e) {
        \Log::error('[createRequestForm] providerItem NOT found', ['providerId' => $providerId, 'error' => $e->getMessage()]);
        abort(404, 'Provider item not found.');
    }

    // Find requested item, if any
    $requestedItem = null;
    if ($requestedId) {
        try {
            $requestedItem = \App\Models\InventoryItem::with('funeralUser', 'category')->findOrFail($requestedId);
            \Log::debug('[createRequestForm] requestedItem found', ['requestedItem' => $requestedItem->toArray()]);
        } catch (\Exception $e) {
            \Log::error('[createRequestForm] requestedItem NOT found', ['requestedId' => $requestedId, 'error' => $e->getMessage()]);
            abort(404, 'Requested item not found.');
        }
    }

    // Only load userItems if needed (option B: "add to existing"), i.e. if no requestedItem and consumable
    $userItems = collect();
    if (!$requestedItem && $providerItem->category && !$providerItem->category->is_asset) {
        $userItems = \App\Models\InventoryItem::where('funeral_home_id', $user->id)
            ->whereHas('category', function ($cat) {
                $cat->where('is_asset', false);
            })
            ->with('category')
            ->orderBy('name')
            ->get();
        \Log::debug('[createRequestForm] userItems loaded', ['count' => $userItems->count()]);
    } else {
        \Log::debug('[createRequestForm] userItems not needed');
    }

    // Categories for new consumable creation (option A)
    $categories = \App\Models\InventoryCategory::where('funeral_home_id', $user->id)
        ->where('is_asset', false)
        ->orderBy('name')
        ->get();
    \Log::debug('[createRequestForm] categories loaded', ['count' => $categories->count()]);

    // Final log before returning the view
    \Log::debug('[createRequestForm] Returning view', [
        'providerItem_id' => $providerItem->id,
        'requestedItem_id' => $requestedItem ? $requestedItem->id : null,
        'userItems_count' => $userItems->count(),
        'categories_count' => $categories->count(),
    ]);

    return view('funeral.partnerships.resource_requests.request_form', [
        'providerItem'  => $providerItem,
        'requestedItem' => $requestedItem,
        'userItems'     => $userItems,
        'categories'    => $categories,
        'user'          => $user,
    ]);
}



public function storeRequest(Request $request)
{
    \Log::debug('[storeRequest] Started', ['input' => $request->all()]);
    try {
        $providerItem = InventoryItem::with('category')->findOrFail($request->input('provider_item_id'));
        \Log::debug('[storeRequest] providerItem loaded', ['provider_item_id' => $providerItem->id]);

        $isAsset = $providerItem->category && $providerItem->category->is_asset;
        $newItemData = [];

        // Consumable flow, requested_item_id may or may not be filled
        if (!$isAsset && !$request->filled('requested_item_id')) {
            $action = $request->input('consumable_action');
            \Log::debug('[storeRequest] Consumable action chosen', ['action' => $action]);
            if ($action === 'new') {
                $request->validate([
                    'new_item_name' => 'required|string|max:255',
                    'new_item_category_id' => 'required|exists:inventory_categories,id',
                    'new_item_brand' => 'nullable|string|max:255',
                ]);
                $newItemData = [
                    'new_item_name'        => $request->input('new_item_name'),
                    'new_item_category_id' => $request->input('new_item_category_id'),
                    'new_item_brand'       => $request->input('new_item_brand') ?? $providerItem->brand,
                ];
                \Log::debug('[storeRequest] newItemData built', $newItemData);
            } elseif ($action === 'existing') {
                $request->validate([
                    'existing_item_id' => [
                        'required',
                        'exists:inventory_items,id',
                        function ($attribute, $value, $fail) {
                            $item = InventoryItem::where('id', $value)
                                ->where('funeral_home_id', auth()->id())
                                ->first();
                            if (!$item || ($item->category && $item->category->is_asset)) {
                                $fail('Invalid inventory item selected.');
                            }
                        }
                    ]
                ]);
                $requestedItem = InventoryItem::findOrFail($request->input('existing_item_id'));
                $request->merge(['requested_item_id' => $requestedItem->id]);
                \Log::debug('[storeRequest] Using existing requested_item_id', ['requested_item_id' => $requestedItem->id]);
            } else {
                \Log::error('[storeRequest] No consumable_action selected');
                return back()->withErrors(['error' => 'Please select how you want to receive the item.']);
            }
        }

        // Validation rules for the main form
        $rules = [
            'provider_item_id'  => 'required|exists:inventory_items,id',
            'purpose'           => 'required|string',
            'delivery_method'   => 'required|string|max:100',
            'notes'             => 'nullable|string',
            'contact_name'      => 'required|string|max:255',
            'contact_mobile'    => 'nullable|string|max:30',
            'contact_email'     => 'nullable|email|max:255',
            'location'          => 'nullable|string|max:255',
        ];

        // For existing consumables only, require requested_item_id
        if (!$isAsset && $request->input('consumable_action') === 'existing') {
            $rules['requested_item_id'] = 'required|exists:inventory_items,id';
        }
        // For assets, DO NOT require requested_item_id
        if ($isAsset) {
            $rules['reserved_start'] = ['required', 'date', 'after_or_equal:today'];
            $rules['reserved_end'] = [
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
                        $fail('This asset is already reserved during the selected period.');
                    }
                }
            ];
        } else {
            $rules['quantity'] = [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($providerItem) {
                    if ($value > $providerItem->shareable_quantity) {
                        $fail('The quantity cannot be greater than the provider\'s shareable quantity (' . $providerItem->shareable_quantity . ').');
                    }
                },
            ];
            $rules['preferred_date'] = 'required|date|after_or_equal:today';
        }

        \Log::debug('[storeRequest] Validating main form', ['rules' => $rules]);
        $validated = $request->validate($rules);
        \Log::debug('[storeRequest] Validation passed', ['validated' => $validated]);

        $requestedItemId = null;
        if (!$isAsset && $request->input('consumable_action') === 'existing') {
            $requestedItemId = $validated['requested_item_id'];
        }
        // For assets: leave as null

        $data = [
            'requester_id'         => auth()->id(),
            'provider_id'          => $providerItem->funeral_home_id,
            'provider_item_id'     => $providerItem->id,
            'requested_item_id'    => $requestedItemId,
            'purpose'              => $validated['purpose'],
            'delivery_method'      => $validated['delivery_method'],
            'notes'                => $validated['notes'] ?? null,
            'contact_name'         => $validated['contact_name'],
            'contact_mobile'       => $validated['contact_mobile'] ?? null,
            'contact_email'        => $validated['contact_email'],
            'location'             => $validated['location'],
            'status'               => 'pending',
            // Default new item fields to null, will overwrite below if present
            'new_item_name'        => null,
            'new_item_category_id' => null,
            'new_item_brand'       => null,
        ];

        if (!empty($newItemData)) {
            $data['new_item_name']        = $newItemData['new_item_name'];
            $data['new_item_category_id'] = $newItemData['new_item_category_id'];
            $data['new_item_brand']       = $newItemData['new_item_brand'];
            \Log::debug('[storeRequest] Merged new item fields into data', $newItemData);
        }

        if ($isAsset) {
            $data['reserved_start'] = $validated['reserved_start'];
            $data['reserved_end']   = $validated['reserved_end'];
            $data['quantity']       = 1;
            $data['preferred_date'] = null;
        } else {
            $data['quantity']       = $validated['quantity'];
            $data['preferred_date'] = $validated['preferred_date'];
            $data['reserved_start'] = null;
            $data['reserved_end']   = null;
        }

        \Log::debug('[storeRequest] Final resource request data', $data);

        $resourceRequest = ResourceRequest::create($data);
        \Log::debug('[storeRequest] ResourceRequest created', ['resource_request_id' => $resourceRequest->id]);

        // Notifications
        $providerUser = \App\Models\User::find($resourceRequest->provider_id);
        $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

        if ($providerUser && $providerUser->id != $requesterUser->id) {
            $providerUser->notify(new \App\Notifications\ResourceRequestNotification($resourceRequest->id, true, 'submitted'));
            \Log::info('[storeRequest] Provider notified', ['providerUserId' => $providerUser->id]);
        }
        if ($requesterUser) {
            $requesterUser->notify(new \App\Notifications\ResourceRequestNotification($resourceRequest->id, false, 'submitted'));
            \Log::info('[storeRequest] Requester notified', ['requesterUserId' => $requesterUser->id]);
        }

        return redirect()->route('funeral.partnerships.resource_requests.index')
            ->with('success', 'Resource request sent successfully!');
    } catch (\Throwable $e) {
        \Log::error('[storeRequest] ERROR', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return back()->withInput()->withErrors(['error' => $e->getMessage()]);
    }
}






}
