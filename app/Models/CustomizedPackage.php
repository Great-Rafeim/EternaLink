<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class CustomizedPackage extends Model
{
    protected $fillable = [
        'booking_id',
        'original_package_id',
        'custom_total_price',
    ];

    // Relationships

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function originalPackage()
    {
        return $this->belongsTo(ServicePackage::class, 'original_package_id');
    }

    public function items()
    {
        return $this->hasMany(CustomizedPackageItem::class);
    }

    // Optional: get total price computed on the fly if needed
    public function getTotalPriceAttribute()
    {
        return $this->items->sum(function($item) {
            return $item->quantity * ($item->unit_price ?? 0);
        });
    }

    public function customItems()
    {
        // This is the new relationship being referenced in your controller/blade
        return $this->hasMany(CustomizedPackageItem::class);
    }

}
