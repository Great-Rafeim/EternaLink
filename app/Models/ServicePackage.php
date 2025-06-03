<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    protected $fillable = [
        'funeral_home_id', 
        'name', 
        'description', 
        'total_price'  // Add total_price since you will update it programmatically
    ];

    public function funeralHome()
    {
        return $this->belongsTo(User::class, 'funeral_home_id');
    }

    public function categories()
    {
        return $this->hasMany(PackageCategory::class, 'service_package_id');
    }

    /**
     * Calculate total price based on items.
     */
    public function calculateTotalPrice()
    {
        $total = 0;
        $this->loadMissing('categories.items');

        foreach ($this->categories as $category) {
            foreach ($category->items as $item) {
                $total += $item->quantity * $item->price;
            }
        }

        return $total;
    }
}
