<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    protected $fillable = [
        'funeral_home_id',
        'name',
        'description',
        'total_price',
    ];

    public function funeralHome()
    {
        return $this->belongsTo(User::class, 'funeral_home_id');
    }

    public function items()
    {
        return $this->belongsToMany(
            \App\Models\InventoryItem::class,
            'inventory_item_service_package',
            'service_package_id',
            'inventory_item_id'
        )->withPivot('quantity')->withTimestamps();
    }

    // You can remove these if you are not using legacy PackageCategory/CategoryItem anymore.
    public function categories()
    {
        return $this->hasMany(PackageCategory::class, 'service_package_id');
    }

    // (optional) Helper
    public function calculateTotalPrice()
    {
        return $this->items->sum(function($item) {
            return $item->pivot->quantity * $item->selling_price;
        });
    }
}
