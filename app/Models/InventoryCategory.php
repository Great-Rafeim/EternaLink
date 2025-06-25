<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_asset',
        'reservation_mode',    // <-- added
        'image',
        'funeral_home_id',

    ];

    protected $casts = [
        'is_asset' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    // Optional scope for assets
    public function scopeAssets($query)
    {
        return $query->where('is_asset', true);
    }

public function packages()
{
    return $this->belongsToMany(
        ServicePackage::class,
        'service_package_components',
        'inventory_category_id',
        'service_package_id'
    );
}


}
