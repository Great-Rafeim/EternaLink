<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    protected $fillable = ['funeral_home_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
