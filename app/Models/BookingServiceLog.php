<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class BookingServiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'message',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

public function user()
{
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}
}
