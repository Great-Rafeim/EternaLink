<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResourceRequest;
use App\Models\AssetReservation;
use App\Models\InventoryItem;
use Carbon\Carbon;

// Notification imports – use your new dedicated notification classes!
use App\Notifications\ResourceRequestSubmittedNotification;
use App\Notifications\ResourceRequestApprovedNotification;
use App\Notifications\ResourceRequestRejectedNotification;
use App\Notifications\ResourceRequestCancelledNotification;
use App\Notifications\ResourceRequestFulfilledNotification;

class ResourceRequestController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $sentRequests = ResourceRequest::where('requester_id', $userId)
            ->with(['requestedItem', 'providerItem', 'provider', 'assetReservation'])
            ->latest()
            ->get();

        $receivedRequests = ResourceRequest::where('provider_id', $userId)
            ->with(['requestedItem', 'providerItem', 'requester', 'assetReservation'])
            ->latest()
            ->get();
        $myCategories = \App\Models\InventoryCategory::where('funeral_home_id', auth()->id())
    ->orderBy('name')->get();

        return view('funeral.partnerships.resource_requests.index', compact('sentRequests', 'receivedRequests', 'myCategories'));
    }

    public function store(Request $request)
    {
        $providerItem = InventoryItem::with('category')->findOrFail($request->input('provider_item_id'));
        $isAsset = $providerItem->category && $providerItem->category->is_asset;

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
            $rules['reserved_start'] = ['required', 'date', 'after_or_equal:now'];
            $rules['reserved_end']   = [
                'required',
                'date',
                'after:reserved_start',
                function ($attribute, $value, $fail) use ($providerItem, $request) {
                    $conflict = AssetReservation::where('inventory_item_id', $providerItem->id)
                        ->where('status', '!=', 'cancelled')
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

        $validated = $request->validate($rules);
        $requestedItem = InventoryItem::findOrFail($validated['requested_item_id']);

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

        $resourceRequest = ResourceRequest::create($data);

        $providerUser = \App\Models\User::find($resourceRequest->provider_id);
        $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

        if ($providerUser && $providerUser->id != $requesterUser->id) {
            $providerUser->notify(new ResourceRequestSubmittedNotification($resourceRequest, true));
        }
        if ($requesterUser) {
            $requesterUser->notify(new ResourceRequestSubmittedNotification($resourceRequest, false));
        }

        return redirect()->route('funeral.partnerships.resource_requests.index')
            ->with('success', 'Resource request sent successfully!');
    }

    public function reject($id, Request $request)
    {
        $resourceRequest = ResourceRequest::findOrFail($id);

        if ($resourceRequest->provider_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        if ($resourceRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending requests can be rejected.');
        }
        $resourceRequest->status = 'rejected';
        $resourceRequest->save();

        if ($resourceRequest->asset_reservation_id) {
            $reservation = AssetReservation::find($resourceRequest->asset_reservation_id);
            if ($reservation) {
                $reservation->status = 'cancelled';
                $reservation->save();
            }
        }

        $providerUser = \App\Models\User::find($resourceRequest->provider_id);
        $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

        if ($providerUser && $providerUser->id != $requesterUser->id) {
            $providerUser->notify(new ResourceRequestRejectedNotification($resourceRequest, true));
        }
        if ($requesterUser) {
            $requesterUser->notify(new ResourceRequestRejectedNotification($resourceRequest, false));
        }

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

    if ($providerItem->category && $providerItem->category->is_asset) {
        // Dates come from the approve modal (user may update them)
        $start = $request->input('reserved_start')
            ? Carbon::parse($request->input('reserved_start'))->startOfDay()
            : ($resourceRequest->reserved_start
                ? Carbon::parse($resourceRequest->reserved_start)
                : Carbon::parse($resourceRequest->preferred_date)->startOfDay());
        $end = $request->input('reserved_end')
            ? Carbon::parse($request->input('reserved_end'))->endOfDay()
            : ($resourceRequest->reserved_end
                ? Carbon::parse($resourceRequest->reserved_end)
                : (clone $start)->addDay());

        // Exclude finished/closed/cancelled reservations
        $activeStatuses = ['reserved', 'pending', 'approved']; // Add any other statuses that mean "active"
        $conflict = AssetReservation::where('inventory_item_id', $providerItem->id)
            ->whereIn('status', $activeStatuses)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('reserved_start', [$start, $end])
                  ->orWhereBetween('reserved_end', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('reserved_start', '<=', $start)
                          ->where('reserved_end', '>=', $end);
                  });
            })
            ->where('id', '!=', $resourceRequest->asset_reservation_id) // Don't conflict with itself
            ->exists();

        if ($conflict) {
            return back()->with('error', "This asset is already reserved during the selected dates.");
        }

        // Create or update the AssetReservation
        if ($resourceRequest->asset_reservation_id) {
            $reservation = AssetReservation::find($resourceRequest->asset_reservation_id);
            if ($reservation) {
                $reservation->status = 'reserved';
                $reservation->reserved_start = $start;
                $reservation->reserved_end = $end;
                $reservation->resource_request_id = $resourceRequest->id;
                $reservation->shared_with_partner_id = $resourceRequest->requester_id;
                $reservation->created_by = $resourceRequest->provider_id;
                $reservation->save();
            }
        } else {
            $reservation = AssetReservation::create([
                'inventory_item_id'       => $providerItem->id,
                'booking_id'              => null,
                'shared_with_partner_id'  => $resourceRequest->requester_id,
                'reserved_start'          => $start,
                'reserved_end'            => $end,
                'status'                  => 'reserved',
                'created_by'              => auth()->id(),
                'resource_request_id'     => $resourceRequest->id,
            ]);
            $resourceRequest->asset_reservation_id = $reservation->id;
        }

        // Update the provider item status (optional: only if not already reserved by someone else)
        $providerItem->status = 'reserved';
        $providerItem->save();

        // Save the reserved dates to the resource request as well
        $resourceRequest->reserved_start = $start;
        $resourceRequest->reserved_end = $end;
    }

    $resourceRequest->status = 'approved';
    $resourceRequest->save();

    $providerUser = \App\Models\User::find($resourceRequest->provider_id);
    $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

    if ($providerUser && $providerUser->id != $requesterUser->id) {
        $providerUser->notify(new ResourceRequestApprovedNotification($resourceRequest, true));
    }
    if ($requesterUser) {
        $requesterUser->notify(new ResourceRequestApprovedNotification($resourceRequest, false));
    }

    return redirect()->back()->with('success', 'Request approved successfully.');
}


    public function show($id)
    {
        $request = ResourceRequest::with([
            'requester',
            'provider',
            'requestedItem',
            'providerItem',
            'assetReservation'
        ])->findOrFail($id);

        if ($request->requester_id !== auth()->id() && $request->provider_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return view('funeral.partnerships.resource_requests.show', [
            'request' => $request,
        ]);
    }

    public function cancel($id)
    {
        $resourceRequest = ResourceRequest::findOrFail($id);

        if ($resourceRequest->requester_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        if ($resourceRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending requests can be cancelled.');
        }

        // 1. Set resource request status to cancelled
        $resourceRequest->status = 'cancelled';
        $resourceRequest->save();

        // 2. If reservation exists, cancel it and update provider item if asset
        if ($resourceRequest->asset_reservation_id) {
            $reservation = AssetReservation::find($resourceRequest->asset_reservation_id);
            if ($reservation) {
                $reservation->status = 'cancelled';
                $reservation->save();

                // Set provider item back to available if asset
                $providerItem = $resourceRequest->providerItem;
                if ($providerItem && $providerItem->category && $providerItem->category->is_asset) {
                    $providerItem->status = 'available';
                    $providerItem->save();
                }
            }
        }

        // 3. Notifications
        $providerUser = \App\Models\User::find($resourceRequest->provider_id);
        $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

        if ($providerUser && $providerUser->id != $requesterUser->id) {
            $providerUser->notify(new ResourceRequestCancelledNotification($resourceRequest, true));
        }
        if ($requesterUser) {
            $requesterUser->notify(new ResourceRequestCancelledNotification($resourceRequest, false));
        }

        return redirect()->back()->with('success', 'Request cancelled successfully.');
    }



public function fulfill(Request $request, $id)
{
    $resourceRequest = ResourceRequest::findOrFail($id);

    if ($resourceRequest->requester_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }
    if ($resourceRequest->status !== 'approved') {
        return back()->with('error', 'Only approved requests can be fulfilled.');
    }

    $providerItem = $resourceRequest->providerItem;
    $requestedItem = $resourceRequest->requestedItem;
    $isAsset = $providerItem->category && $providerItem->category->is_asset;

    \DB::beginTransaction();
    try {
        if ($isAsset) {
            // 1. Validate category input
            $request->validate([
                'inventory_category_id' => [
                    'required',
                    // Only categories belonging to this borrower's funeral_home
                    function ($attribute, $value, $fail) use ($resourceRequest) {
                        $exists = \App\Models\InventoryCategory::where('id', $value)
                            ->where('funeral_home_id', $resourceRequest->requester_id)
                            ->exists();
                        if (!$exists) {
                            $fail('Invalid category selected.');
                        }
                    }
                ]
            ]);

            $reservation = null;

            if ($resourceRequest->asset_reservation_id) {
                $reservation = AssetReservation::find($resourceRequest->asset_reservation_id);
            } else {
                $reservation = AssetReservation::where('inventory_item_id', $providerItem->id)
                    ->where('shared_with_partner_id', $resourceRequest->requester_id)
                    ->where('created_by', $resourceRequest->provider_id)
                    ->where('status', 'reserved')
                    ->latest('reserved_start')
                    ->first();
            }

            // If no reservation, create it
            if (!$reservation) {
                $reservation = AssetReservation::create([
                    'inventory_item_id'       => $providerItem->id,
                    'reserved_start'          => $resourceRequest->reserved_start ?? now(),
                    'reserved_end'            => $resourceRequest->reserved_end ?? now()->addDay(),
                    'status'                  => 'reserved',
                    'created_by'              => $resourceRequest->provider_id,
                    'shared_with_partner_id'  => $resourceRequest->requester_id,
                    'resource_request_id'     => $resourceRequest->id,
                ]);
                $resourceRequest->asset_reservation_id = $reservation->id;
                $resourceRequest->save();
            } else {
                $updateNeeded = false;
                if (!$reservation->shared_with_partner_id) {
                    $reservation->shared_with_partner_id = $resourceRequest->requester_id;
                    $updateNeeded = true;
                }
                if (!$reservation->created_by) {
                    $reservation->created_by = $resourceRequest->provider_id;
                    $updateNeeded = true;
                }
                if (!$reservation->resource_request_id) {
                    $reservation->resource_request_id = $resourceRequest->id;
                    $updateNeeded = true;
                }
                if ($updateNeeded) $reservation->save();
            }

            // 2. Mark provider's asset as shared_to_partner
            $providerItem->status = 'shared_to_partner';
            $providerItem->save();

            // 3. Create the borrowed asset in borrower's inventory with selected category
            $borrowedAsset = new InventoryItem([
                'funeral_home_id'         => $resourceRequest->requester_id,
                'inventory_category_id'   => $request->input('inventory_category_id'), // use selection!
                'name'                    => $providerItem->name,
                'brand'                   => $providerItem->brand,
                'quantity'                => 1,
                'low_stock_threshold'     => $providerItem->low_stock_threshold,
                'status'                  => 'borrowed_from_partner',
                'price'                   => $providerItem->price,
                'selling_price'           => $providerItem->selling_price,
                'shareable'               => 0,
                'shareable_quantity'      => 0,
                'expiry_date'             => $providerItem->expiry_date,
                'is_borrowed'             => 1,
                'borrowed_from_id'        => $resourceRequest->provider_id,
                'borrowed_reservation_id' => $reservation->id,
                'borrowed_start'          => $reservation->reserved_start,
                'borrowed_end'            => $reservation->reserved_end,
            ]);
            $borrowedAsset->save();

            $reservation->status = 'in_use';
            $reservation->borrowed_item_id = $borrowedAsset->id;
            $reservation->save();
        } else {
            // For consumables: move stock
            if ($providerItem && $requestedItem) {
                $providerItem->shareable_quantity = max(0, $providerItem->shareable_quantity - $resourceRequest->quantity);
                $providerItem->quantity = max(0, $providerItem->quantity - $resourceRequest->quantity);
                $providerItem->save();

                $requestedItem->quantity += $resourceRequest->quantity;
                $requestedItem->save();
            }
        }

        // 4. Mark request as fulfilled
        $resourceRequest->status = 'fulfilled';
        $resourceRequest->save();

        $providerUser = \App\Models\User::find($resourceRequest->provider_id);
        $requesterUser = \App\Models\User::find($resourceRequest->requester_id);

        if ($providerUser && $providerUser->id != $requesterUser->id) {
            $providerUser->notify(new ResourceRequestFulfilledNotification($resourceRequest, true));
        }
        if ($requesterUser) {
            $requesterUser->notify(new ResourceRequestFulfilledNotification($resourceRequest, false));
        }

        \DB::commit();
        return redirect()->route('funeral.partnerships.resource_requests.index')
            ->with('success', 'Request marked as fulfilled and inventory updated!');
    } catch (\Exception $e) {
        \DB::rollBack();
        return back()->with('error', 'An error occurred: ' . $e->getMessage());
    }
}


}
