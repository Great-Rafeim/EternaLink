<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CemeteryBooking extends Model
{
    use HasFactory;

    protected $table = 'cemetery_bookings';

    protected $fillable = [
        'user_id',
        'cemetery_id',
        'booking_id',
        'casket_size',
        'interment_date',
        'status',
        'admin_notes',
        'death_certificate_path',
        'burial_permit_path',
        'construction_permit_path',
        'proof_of_purchase_path',
    ];

    protected $casts = [
        'interment_date' => 'date',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function cemetery()
    {
        // Assuming the 'cemetery_id' is the foreign key
        return $this->belongsTo(\App\Models\Cemetery::class, 'cemetery_id', 'id');
    }
public function actualCemeteryUser()
{
    return $this->cemetery ? $this->cemetery->user : null;
}
public function actualAgentUser()
{
    $funeralBooking = $this->funeralBooking;
    if ($funeralBooking) {
        // Find in booking_agents table by booking_id
        $bookingAgent = \DB::table('booking_agents')
            ->where('booking_id', $funeralBooking->id)
            ->whereNotNull('agent_user_id')
            ->orderByDesc('id') // if multiple rows, get the latest
            ->first();

        if ($bookingAgent && $bookingAgent->agent_user_id) {
            return \App\Models\User::find($bookingAgent->agent_user_id);
        }
    }
    return null;
}




    // User (the client making the booking)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plot()
    {
        return $this->belongsTo(\App\Models\Plot::class, 'plot_id');
    }
public function client()
{
    // Adjust 'user_id' to 'client_id' if your column is named that way
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}
    // Related funeral booking
    public function funeralBooking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // Get URLs for documents
    public function getDeathCertificateUrlAttribute()
    {
        return $this->death_certificate_path ? asset('storage/' . $this->death_certificate_path) : null;
    }
    public function getBurialPermitUrlAttribute()
    {
        return $this->burial_permit_path ? asset('storage/' . $this->burial_permit_path) : null;
    }
    public function getConstructionPermitUrlAttribute()
    {
        return $this->construction_permit_path ? asset('storage/' . $this->construction_permit_path) : null;
    }
    public function getProofOfPurchaseUrlAttribute()
    {
        return $this->proof_of_purchase_path ? asset('storage/' . $this->proof_of_purchase_path) : null;
    }





}
