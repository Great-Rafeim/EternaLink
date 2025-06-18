<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'booking_id',
        'reserved_start',
        'reserved_end',
        'status',
        'created_by',
    ];

    protected $casts = [
        'reserved_start' => 'datetime',
        'reserved_end' => 'datetime',
    ];

    // Relationship to InventoryItem
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


}
