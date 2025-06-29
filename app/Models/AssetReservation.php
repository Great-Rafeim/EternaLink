<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class AssetReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'booking_id',
        'shared_with_partner_id',
        'reserved_start',
        'reserved_end',
        'status',
        'created_by',
        // New:
        'borrowed_item_id',
        'resource_request_id',
    ];

    protected $casts = [
        'reserved_start' => 'datetime',
        'reserved_end' => 'datetime',
    ];

    // In your AssetReservation model
protected static function booted()
{
    static::creating(function ($reservation) {
        // Only require resource_request_id for partner asset reservations
        $isPartner = 
            $reservation->shared_with_partner_id || 
            $reservation->borrowed_item_id || 
            ($reservation->inventoryItem && $reservation->inventoryItem->is_borrowed);

        if ($isPartner && is_null($reservation->resource_request_id)) {
            throw new \Exception('resource_request_id must be set for partner asset reservations');
        }
    });
}




    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sharedWithPartner()
    {
        return $this->belongsTo(User::class, 'shared_with_partner_id');
    }

    public function resourceRequest()
    {
        return $this->belongsTo(ResourceRequest::class, 'resource_request_id');
    }

    // NEW: Relationship to borrowed inventory item (for tracking)
    public function borrowedItem()
    {
        return $this->belongsTo(InventoryItem::class, 'borrowed_item_id');
    }


}
