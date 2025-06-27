<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CemeteryBookingDocument extends Model
{
    use HasFactory;

    protected $table = 'cemetery_booking_documents';

    protected $fillable = [
        'cemetery_booking_id',
        'type',
        'file_path',
        'uploaded_at',
    ];

    public $timestamps = false; // We use 'uploaded_at' instead

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    // The booking this document belongs to
    public function booking()
    {
        return $this->belongsTo(CemeteryBooking::class, 'cemetery_booking_id');
    }
}
