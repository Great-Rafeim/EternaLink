<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    protected $fillable = ['funeral_home_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
