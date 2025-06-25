<?php

namespace App\Http\Controllers;

use App\Models\AssetReservation;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\AssetReservationStatusChangedNotification;

class AssetReservationController extends Controller
{
public function index(Request $request)
{
    $user = auth()->user();
    $funeralHomeId = $user->id; // Or $user->funeral_home_id if you store it separately

    $query = AssetReservation::with([
        'inventoryItem.category',
        'booking.client',
        'booking.agent',
        'creator',
        'sharedWithPartner',
        'resourceRequest'
    ])
    ->where(function($q) use ($funeralHomeId) {
        $q
        // Your funeral home is the owner/provider
        ->whereHas('inventoryItem', function($sub) use ($funeralHomeId) {
            $sub->where('funeral_home_id', $funeralHomeId);
        })
        // OR your funeral home is the borrower of a partner asset (borrowed_from_partner)
        ->orWhereHas('inventoryItem', function($sub) use ($funeralHomeId) {
            $sub->where('status', 'borrowed_from_partner')
                ->where('funeral_home_id', $funeralHomeId);
        });
    });

    // Status filter
    $status = $request->input('status', 'active');
    if ($status === 'active') {
        $query->whereIn('status', ['reserved', 'in_use']);
    } elseif ($status !== 'all') {
        $query->where('status', $status);
    }

    // Category filter
    if ($request->filled('category')) {
        $query->whereHas('inventoryItem.category', function($q) use ($request) {
            $q->where('id', $request->category);
        });
    }
    // Asset filter
    if ($request->filled('asset')) {
        $query->where('inventory_item_id', $request->asset);
    }

    $reservations = $query->orderByDesc('reserved_start')->paginate(20);
    $categories = \App\Models\InventoryCategory::where('is_asset', true)->orderBy('name')->get();
    $assets = \App\Models\InventoryItem::whereHas('category', function ($q) {
        $q->where('is_asset', true);
    })->orderBy('name')->get();

    return view('funeral.assets.reservations.index', compact('reservations', 'categories', 'assets'));
}



public function cancel(Request $request, AssetReservation $reservation)
{
    $userId = auth()->id();
    $resourceRequest = $reservation->resourceRequest;

    \Log::info('CANCEL DEBUG', [
        'user_id' => $userId,
        'resource_request_id' => $reservation->resource_request_id,
        'resourceRequest' => $resourceRequest ? $resourceRequest->toArray() : null
    ]);

    $isRequester = $resourceRequest && $userId === $resourceRequest->requester_id;
    $isProvider  = $resourceRequest && $userId === $resourceRequest->provider_id;

    if (!$isRequester && !$isProvider) {
        abort(403);
    }
    if ($reservation->status !== 'reserved') {
        return back()->with('error', 'Only reserved bookings can be cancelled.');
    }

    DB::transaction(function () use ($reservation, $resourceRequest) {
        $reservation->status = 'cancelled';
        $reservation->save();

        // THIS LINE MAKES SURE THE RESOURCE REQUEST IS ALSO CANCELLED
        if ($resourceRequest && !in_array($resourceRequest->status, ['cancelled', 'rejected'])) {
            $resourceRequest->status = 'cancelled';
            $resourceRequest->save();
        }

        $item = $reservation->inventoryItem;
        if ($item && $item->status === 'reserved') {
            $item->status = 'available';
            $item->save();
        }
    });

    $this->notifyParties($reservation, 'cancelled');
    return back()->with('success', 'Reservation cancelled.');
}


    public function returnAsset(Request $request, AssetReservation $reservation)
    {
        $userId = auth()->id();
        $resourceRequest = $reservation->resourceRequest;
        $isRequester = $resourceRequest && $userId === $resourceRequest->requester_id;

        if (!$isRequester) {
            abort(403);
        }
        if ($reservation->status !== 'in_use') {
            return back()->with('error', 'Asset is not currently in use.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->status = 'completed';
            $reservation->reserved_end = now();
            $reservation->save();
            // NO inventory update yet!
        });

        $this->notifyParties($reservation, 'completed');
        return back()->with('success', 'Asset returned. Waiting for provider to mark as received.');
    }

    public function receive(Request $request, AssetReservation $reservation)
    {
        $userId = auth()->id();
        $resourceRequest = $reservation->resourceRequest;
        $isProvider = $resourceRequest && $userId === $resourceRequest->provider_id;

        if (!$isProvider) {
            abort(403);
        }
        if ($reservation->status !== 'completed') {
            return back()->with('error', 'Asset must be returned first.');
        }

        DB::transaction(function () use ($reservation) {
            // 1. Set provider's item (shared_to_partner) to available
            $providerItem = $reservation->inventoryItem;
            if ($providerItem && $providerItem->status === 'shared_to_partner') {
                $providerItem->status = 'available';
                $providerItem->save();
            }

            // 2. Remove the borrowed asset from requestor's inventory
            if ($reservation->borrowed_item_id) {
                $borrowedItem = InventoryItem::find($reservation->borrowed_item_id);
                if ($borrowedItem && $borrowedItem->status === 'borrowed_from_partner') {
                    $borrowedItem->delete();
                }
            }

            // Set reservation status to closed
            $reservation->status = 'closed';
            $reservation->save();
        });

        $this->notifyParties($reservation, 'received');
        return back()->with('success', 'Asset receipt acknowledged. Inventory updated.');
    }

public function updateStatus(Request $request, AssetReservation $reservation)
{
    $request->validate([
        'status' => 'required|in:reserved,in_use,completed,cancelled,available,closed'
    ]);

    // Only allow staff to update funeral booking reservations:
    if ($reservation->booking_id && !auth()->user()->isFuneralStaff()) {
        abort(403, 'Unauthorized.');
    }
    // Add further policy checks if necessary

    DB::transaction(function () use ($request, $reservation) {
        $reservation->status = $request->status;
        $reservation->manual_override = true; // <-- Mark as manually set
        $reservation->save();

        $item = $reservation->inventoryItem;
        if ($item) {
            // Only update status if NOT borrowed_from_partner
            if ($item->status !== 'borrowed_from_partner') {
                switch ($request->status) {
                    case 'reserved':
                        $item->status = 'reserved'; break;
                    case 'in_use':
                        $item->status = 'in_use'; break;
                    case 'completed':
                    case 'closed':
                    case 'available':
                    case 'cancelled':
                    default:
                        $item->status = 'available'; break;
                }
                $item->save();
            }
        }
    });

    // Optionally notify parties (client, agent, staff) if desired.

    return back()->with('success', 'Asset reservation status updated.');
}


    protected function notifyParties(AssetReservation $reservation, $action)
    {
        $resourceRequest = $reservation->resourceRequest;
        $provider = $resourceRequest && $resourceRequest->provider ? $resourceRequest->provider : null;
        $requester = $resourceRequest && $resourceRequest->requester ? $resourceRequest->requester : null;

        if ($provider) {
            $provider->notify(new AssetReservationStatusChangedNotification($reservation, $action, 'provider'));
        }
        if ($requester) {
            $requester->notify(new AssetReservationStatusChangedNotification($reservation, $action, 'requester'));
        }
    }
}
