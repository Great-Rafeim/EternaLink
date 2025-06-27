<?php

/*
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssetReservation;
use App\Models\InventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAssetReservationStatuses extends Command
{
    protected $signature = 'assets:update-statuses';
    protected $description = 'Automatically update asset reservation and item statuses based on time';

    public function handle()
    {
        $now = Carbon::now();

        // 1. Update reservations: reserved → in_use if in window, unless manual_override
        $handoverReservations = AssetReservation::where('status', 'reserved')
            ->where('reserved_start', '<=', $now)
            ->where('reserved_end', '>', $now)
            ->where(function ($q) {
                $q->whereNull('manual_override')->orWhere('manual_override', false);
            })
            ->get();

            foreach ($handoverReservations as $reservation) {
                DB::transaction(function () use ($reservation, $now) {
                    $reservation->status = 'in_use';
                    $reservation->save();

                    $item = $reservation->inventoryItem;

                    if ($item && $reservation->shared_with_partner_id) {
                        // If it's already shared, don't touch its status!
                        if ($item->status === 'shared_to_partner') {
                            // No further status update needed.
                        } elseif ($item->status === 'reserved') {
                            // Move to shared_to_partner only at the reservation start
                            if ($now->isSameDay(Carbon::parse($reservation->reserved_start))) {
                                $item->status = 'shared_to_partner';
                                $item->save();
                            }
                        }
                        // Borrowed item logic unchanged
                        if (!$reservation->borrowed_item_id) {
                            $borrowed = InventoryItem::create([
                                'funeral_home_id'         => $reservation->shared_with_partner_id,
                                'inventory_category_id'   => $item->inventory_category_id,
                                'name'                    => $item->name,
                                'brand'                   => $item->brand,
                                'quantity'                => 1,
                                'low_stock_threshold'     => $item->low_stock_threshold,
                                'status'                  => 'borrowed_from_partner',
                                'price'                   => $item->price,
                                'selling_price'           => $item->selling_price,
                                'shareable'               => 0,
                                'shareable_quantity'      => 0,
                                'expiry_date'             => $item->expiry_date,
                                'is_borrowed'             => 1,
                                'borrowed_from_id'        => $item->funeral_home_id,
                                'borrowed_reservation_id' => $reservation->id,
                                'borrowed_start'          => $reservation->reserved_start,
                                'borrowed_end'            => $reservation->reserved_end,
                            ]);
                            $reservation->borrowed_item_id = $borrowed->id;
                            $reservation->save();
                        }
                    } elseif ($item && $item->status === 'reserved') {
                        // Standard in_use transition for non-partnered
                        $item->status = 'in_use';
                        $item->save();
                    }
                });
            }

        // 2. Update reservations: reserved/in_use → completed if end has passed (unless manual_override)
        $completedReservations = AssetReservation::whereIn('status', ['reserved', 'in_use'])
            ->where('reserved_end', '<=', $now)
            ->where(function ($q) {
                $q->whereNull('manual_override')->orWhere('manual_override', false);
            })
            ->get();

        foreach ($completedReservations as $reservation) {
            DB::transaction(function () use ($reservation) {
                $reservation->status = 'completed';
                $reservation->save();
                // No change to inventory yet
            });
        }

        // 3. Update reservations: closed → inventory available, cleanup borrowed items
        $closedReservations = AssetReservation::where('status', 'closed')->get();

        foreach ($closedReservations as $reservation) {
            DB::transaction(function () use ($reservation) {
                $item = $reservation->inventoryItem;
                // Only set to available if **no other active reservation**
                if ($item) {
                    $activeCount = AssetReservation::where('inventory_item_id', $item->id)
                        ->whereIn('status', ['reserved', 'in_use', 'completed'])
                        ->where('id', '!=', $reservation->id)
                        ->where('reserved_end', '>', Carbon::now())
                        ->count();
                    if ($activeCount == 0 && $item->status !== 'available') {
                        $item->status = 'available';
                        $item->save();
                    }
                }
                // Delete borrowed asset when closed
                if ($reservation->borrowed_item_id) {
                    $borrowedItem = InventoryItem::find($reservation->borrowed_item_id);
                    if ($borrowedItem) {
                        $borrowedItem->delete();
                    }
                    $reservation->borrowed_item_id = null;
                    $reservation->save();
                }
            });
        }

        // 4. For all assets: Sync their status with their current reservation, or set to available if none.
        InventoryItem::chunk(100, function ($items) use ($now) {
            foreach ($items as $item) {
                // Skip borrowed_from_partner: handled by borrowing logic
                if ($item->status === 'borrowed_from_partner') continue;

                $active = AssetReservation::where('inventory_item_id', $item->id)
                    ->whereIn('status', ['reserved', 'in_use'])
                    ->where('reserved_start', '<=', $now)
                    ->where('reserved_end', '>=', $now)
                    ->orderBy('reserved_start', 'desc')
                    ->first();

                if ($active) {
                    // Status mirroring
                    if ($active->status === 'in_use' && $item->status !== 'in_use') {
                        $item->status = 'in_use';
                        $item->save();
                    } elseif ($active->status === 'reserved' && $item->status !== 'reserved') {
                        $item->status = 'reserved';
                        $item->save();
                    }
                    // If asset is being shared to partner, keep status
                    // If it's 'shared_to_partner', only set back to 'reserved' if NOT shared now
                } else {
                    // No active reservation, set to available (if not borrowed)
                    if (!in_array($item->status, ['shared_to_partner', 'borrowed_from_partner', 'maintenance'])) {
                        if ($item->status !== 'available') {
                            $item->status = 'available';
                            $item->save();
                        }
                    }
                }
            }
        });

        // 5. Safety: Future reservations are set to reserved, but status update is handled above

        $this->info('Asset reservation and inventory statuses auto-synced! (Manual overrides respected)');
    }
}
*/