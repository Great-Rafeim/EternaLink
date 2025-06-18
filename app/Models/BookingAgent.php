<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingAgent extends Model
{
    protected $fillable = [
        'booking_id',
        'need_agent',
        'agent_type',
        'client_agent_email',
        'agent_user_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
