<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    // Make sure to include 'is_asset' and 'funeral_home_id'!
    protected $fillable = [
        'name',
        'description',
        'is_asset',
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
}
