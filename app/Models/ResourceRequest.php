<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceRequest extends Model
{
    // Mass assignable fields (make sure these match your migration)
    protected $fillable = [
        'requester_id',
        'provider_id',
        'requested_item_id',
        'provider_item_id',
        'quantity',
        'purpose',
        'preferred_date',
        'delivery_method',
        'notes',
        'contact_name',
        'contact_mobile',
        'contact_email',
        'location',
        'status',
    ];

    // Relationships (optional but recommended for easy data access)
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
}
