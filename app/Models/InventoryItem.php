<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'funeral_home_id',
        'inventory_category_id',
        'name',
        'brand',
        'image',
        'quantity',
        'low_stock_threshold',
        'status',
        'price',
        'selling_price',
        'shareable',
        'shareable_quantity',
        'expiry_date',
        // New fields:
        'is_borrowed',
        'borrowed_from_id',
        'borrowed_reservation_id',
        'borrowed_start',
        'borrowed_end',
    ];

    protected $casts = [
        'borrowed_start' => 'datetime',
        'borrowed_end' => 'datetime',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function funeralUser()
    {
        return $this->belongsTo(User::class, 'funeral_home_id');
    }

    public function servicePackages()
    {
        return $this->belongsToMany(
            \App\Models\ServicePackage::class,
            'service_package_components',
            'inventory_item_id',
            'service_package_id'
        )->withPivot('quantity')->withTimestamps();
    }

    public function assetReservations()
    {
        return $this->hasMany(AssetReservation::class, 'inventory_item_id');
    }

    // NEW: Relationships for borrowed asset
    public function borrowedFrom()
    {
        return $this->belongsTo(User::class, 'borrowed_from_id');
    }

    public function borrowedReservation()
    {
        return $this->belongsTo(AssetReservation::class, 'borrowed_reservation_id');
    }
}
