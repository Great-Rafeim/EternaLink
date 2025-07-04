<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
