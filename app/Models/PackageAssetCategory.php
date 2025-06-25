<?php

// app/Models/PackageAssetCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageAssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_package_id',
        'inventory_category_id',
        'price',
    ];

    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function inventoryCategory()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }
    public function assetCategories()
    {
        return $this->hasMany(PackageAssetCategory::class, 'service_package_id');
    }

}
