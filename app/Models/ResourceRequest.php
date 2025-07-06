<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class ResourceRequest extends Model
{
    // Mass assignable fields - must match your latest migration
    protected $fillable = [
        'requester_id',
        'provider_id',
        'requested_item_id',
        'provider_item_id',
        'quantity',
        'purpose',
        'preferred_date',
        'reserved_start',     // NEW
        'reserved_end',       // NEW
        'asset_reservation_id', // NEW
        'delivery_method',
        'notes',
        'contact_name',
        'contact_mobile',
        'contact_email',
        'location',
        'status',
                'new_item_name',
        'new_item_category_id',
        'new_item_brand',
    ];

    // Relationships (recommended)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function requestedItem()
    {
        return $this->belongsTo(InventoryItem::class, 'requested_item_id');
    }

    public function providerItem()
    {
        return $this->belongsTo(InventoryItem::class, 'provider_item_id');
    }

    // If asset_reservation_id is in resource_requests table
    public function assetReservation()
    {
        return $this->belongsTo(AssetReservation::class, 'asset_reservation_id');
    }
    // If you want to also allow reverse lookup from AssetReservation:
    // (in AssetReservation model)
    public function resourceRequest() {
         return $this->hasOne(ResourceRequest::class, 'asset_reservation_id');
    }
}
