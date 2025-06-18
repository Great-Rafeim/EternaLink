<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomizedPackageItem extends Model
{
    protected $fillable = [
        'customized_package_id',
        'inventory_item_id',
        'substitute_for',
        'quantity',
        'unit_price',
    ];

    // Relationships

    public function customizedPackage()
    {
        return $this->belongsTo(CustomizedPackage::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    // The original item this is a substitute for (if any)
    public function substituteFor()
    {
        return $this->belongsTo(InventoryItem::class, 'substitute_for');
    }
}
