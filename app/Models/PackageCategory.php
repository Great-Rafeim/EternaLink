<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageCategory extends Model
{
    protected $fillable = [
        'service_package_id', 
        'name'
    ];

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function items()
    {
        return $this->hasMany(CategoryItem::class, 'package_category_id');
    }
}

