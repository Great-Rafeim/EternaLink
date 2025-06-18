<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssetReservation;
use Carbon\Carbon;

class UpdateAssetReservationStatus extends Command
{
    protected $signature = 'asset-reservations:update-status';
    protected $description = 'Update asset reservation status based on time';

    public function handle()
    {
        $now = Carbon::now();

        // Reserved → In Use
        AssetReservation::where('status', 'reserved')
            ->where('reserved_start', '<=', $now)
            ->where('reserved_end', '>=', $now)
            ->each(function ($r) {
                $r->status = 'in_use';
                $r->inventoryItem->status = 'in_use';
                $r->inventoryItem->save();
                $r->save();
            });

        // In Use → Completed
        AssetReservation::where('status', 'in_use')
            ->where('reserved_end', '<=', $now)
            ->each(function ($r) {
                $r->status = 'completed';
                $r->inventoryItem->status = 'available';
                $r->inventoryItem->save();
                $r->save();
            });

        $this->info('Asset reservations statuses updated.');
    }
}
