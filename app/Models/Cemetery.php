<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cemetery extends Model
{
    protected $fillable = [
            'user_id',
    'address',
    'contact_number',
    'description',
    'image_path',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
    public function plots()
    {
        return $this->hasMany(Plot::class);
    }
}
