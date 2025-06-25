<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackageComponent extends Model
{
    protected $table = 'service_package_components';

    protected $fillable = [
        'service_package_id',
        'inventory_item_id',
        'inventory_category_id',
        'quantity',
    ];

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function inventoryCategory()
    {
        return $this->belongsTo(InventoryCategory::class);
    }
}
