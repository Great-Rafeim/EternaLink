<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class CategoryItem extends Model
{
    protected $fillable = [
        'package_category_id', 
        'name', 
        'quantity', 
        'description', 
        'price'
    ];

    public function category()
    {
        return $this->belongsTo(PackageCategory::class, 'package_category_id');
    }
}

