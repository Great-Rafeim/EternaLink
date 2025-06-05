<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Partnership extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'partner_id',
        'status',
    ];

    // The parlor who sent the request
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // The parlor who receives the request
    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
