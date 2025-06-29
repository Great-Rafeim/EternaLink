<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use App\Models\PackageAssetCategory;

class ServicePackage extends Model
{
    protected $fillable = [
        'funeral_home_id',
        'name',
        'description',
        'total_price',
        'image',
    ];

    public function funeralHome()
    {
        return $this->belongsTo(User::class, 'funeral_home_id');
    }

    // Consumable Items (item-level linkage)
    public function items()
    {
        return $this->belongsToMany(
            InventoryItem::class,
            'service_package_components',
            'service_package_id',
            'inventory_item_id'
        )->withPivot('quantity')->withTimestamps()
         ->wherePivotNotNull('inventory_item_id');
    }

    // Bookable Asset Categories (category-level pricing)
    public function assetCategories()
    {
        return $this->hasMany(PackageAssetCategory::class, 'service_package_id');
    }

    // Helper: sum all asset category prices
    public function assetCategoriesTotalPrice()
    {
        return $this->assetCategories->sum('price');
    }

    // Helper: sum total package price (items + asset categories)
    public function calculateTotalPrice()
    {
        $itemsTotal = $this->items->sum(function($item) {
            return $item->pivot->quantity * ($item->selling_price ?? 0);
        });
        $assetsTotal = $this->assetCategories->sum('price');

        return $itemsTotal + $assetsTotal;
    }

    // Optional: get all components (pivot records)
    public function components()
    {
        return $this->hasMany(ServicePackageComponent::class, 'service_package_id');
    }
}
