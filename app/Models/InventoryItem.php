<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'funeral_home_id', // include this if present!
        'inventory_category_id',
        'name',
        'brand',
        'quantity',
        'low_stock_threshold',
        'status',
        'price',
        'selling_price',
        'shareable',
        'shareable_quantity',
        'expiry_date', // if you added this
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

    public function servicePackages()
    {
        return $this->belongsToMany(
            \App\Models\ServicePackage::class,
            'inventory_item_service_package',
            'inventory_item_id',
            'service_package_id'
        )->withPivot('quantity')->withTimestamps();
    }

    public function funeralUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'funeral_home_id');
    }

    public function assetReservations()
    {
        return $this->hasMany(AssetReservation::class, 'inventory_item_id');
    }

}
