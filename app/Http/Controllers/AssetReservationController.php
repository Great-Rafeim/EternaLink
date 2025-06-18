<?php

namespace App\Http\Controllers;
use App\Http\Controllers\AssetReservationController;


use App\Models\AssetReservation;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class AssetReservationController extends Controller
{
    // Show all asset reservations with optional filters
    public function index(Request $request)
    {
        $query = AssetReservation::with(['inventoryItem.category', 'booking.client', 'booking.agent', 'creator']);

        // Optional filter by asset/category/status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('category') && $request->category) {
            $query->whereHas('inventoryItem.category', function($q) use ($request) {
                $q->where('id', $request->category);
            });
        }
        if ($request->has('asset') && $request->asset) {
            $query->where('inventory_item_id', $request->asset);
        }

        $reservations = $query->orderBy('reserved_start', 'desc')->paginate(20);

        $categories = \App\Models\InventoryCategory::where('is_asset', true)->get();
        $assets = InventoryItem::whereHas('category', fn($q) => $q->where('is_asset', true))->get();

        return view('funeral.assets.reservations.index', compact('reservations', 'categories', 'assets'));
    }

    // Change status (manual override)
    public function updateStatus(Request $request, AssetReservation $reservation)
    {
        $request->validate([
            'status' => 'required|in:reserved,in_use,completed,cancelled'
        ]);

        $reservation->status = $request->status;
        $reservation->save();

        // Optionally, update the asset status too (if completed/cancelled, set to available)
        if (in_array($request->status, ['completed', 'cancelled'])) {
            $reservation->inventoryItem->status = 'available';
            $reservation->inventoryItem->save();
        } elseif ($request->status === 'reserved') {
            $reservation->inventoryItem->status = 'reserved';
            $reservation->inventoryItem->save();
        } elseif ($request->status === 'in_use') {
            $reservation->inventoryItem->status = 'in_use';
            $reservation->inventoryItem->save();
        }

        return back()->with('success', 'Asset reservation status updated.');
    }
}
