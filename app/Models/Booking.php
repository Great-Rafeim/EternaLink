<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class Booking extends Model
{
    // ---- STATUS CONSTANTS ----
    public const STATUS_PENDING      = 'pending';      // Client just requested
    public const STATUS_CONFIRMED    = 'confirmed';    // Funeral parlor pre-approval (client can start filling forms)
    public const STATUS_FOR_PAYMENT_DETAILS = 'for_payment_details'; // Client can now fill in payment and certification
    public const STATUS_IN_PROGRESS  = 'in_progress';  // Client is filling up booking forms
    public const STATUS_SUBMITTED    = 'for_review';    // Client submitted booking forms, pending final parlor review
    public const STATUS_APPROVED     = 'approved';     // Final parlor approval of all details
    public const STATUS_ONGOING      = 'ongoing';      // Funeral service started
    public const STATUS_COMPLETED    = 'completed';    // Service is done
    public const STATUS_DECLINED     = 'declined';
    public const STATUS_CANCELLED    = 'cancelled';
    public const STATUS_FOR_INITIAL_REVIEW = 'for_initial_review';    // After Phase 3, waiting for parlor to set fees
    public const STATUS_FOR_FINAL_REVIEW = 'for_final_review';    // After Phase 3, waiting for parlor to set fees
    protected $fillable = [
        'client_user_id',
        'funeral_home_id',
        'package_id',
        'customized_package_id',
        'agent_user_id',
        'status',
        'final_amount',
        'details',
        'death_certificate_path',
    ];

    // RELATIONSHIPS
    public function getDecodedDetailsAttribute()
    {
        return $this->details ? json_decode($this->details, true) : [];
    }
public function cemeteryBooking()
{
    return $this->hasOne(\App\Models\CemeteryBooking::class, 'booking_id');
}
    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function funeralHome()
    {
        return $this->belongsTo(User::class, 'funeral_home_id');
    }

    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }
    public function clientUser()
{
    return $this->belongsTo(\App\Models\User::class, 'client_user_id');
}

    public function detail()
    {
        return $this->hasOne(BookingDetail::class, 'booking_id');
    }

    public function customizedPackage()
    {
        return $this->belongsTo(CustomizedPackage::class, 'customized_package_id');
    }

    // In App\Models\Booking

public function latestCustomizationRequest()
{
    return $this->hasOne(CustomizedPackage::class, 'booking_id')->latestOfMany();
}

// Or for all requests:
public function customizationRequests()
{
    return $this->hasMany(CustomizedPackage::class, 'booking_id');
}


    public function agentAssignment()
    {
        return $this->hasOne(BookingAgent::class, 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    // ---- STATUS LABELS FOR VIEWS ----
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING     => ['label' => 'Pending', 'color' => 'warning',   'icon' => 'hourglass-split'],
            self::STATUS_CONFIRMED   => ['label' => 'Confirmed', 'color' => 'info',    'icon' => 'check-circle'],
            self::STATUS_IN_PROGRESS => ['label' => 'Client Filling Forms', 'color' => 'secondary', 'icon' => 'pencil-square'],
            self::STATUS_FOR_PAYMENT_DETAILS => ['label' => 'For Payment Details', 'color' => 'primary','icon'  => 'wallet'],
            self::STATUS_SUBMITTED   => ['label' => 'For Final Review', 'color' => 'warning', 'icon' => 'journal-check'],
            self::STATUS_APPROVED    => ['label' => 'Approved',  'color' => 'success', 'icon' => 'hand-thumbs-up'],
            self::STATUS_ONGOING     => ['label' => 'Ongoing',   'color' => 'primary', 'icon' => 'arrow-repeat'],
            self::STATUS_COMPLETED   => ['label' => 'Completed', 'color' => 'success', 'icon' => 'award'],
            self::STATUS_DECLINED    => ['label' => 'Declined',  'color' => 'danger',  'icon' => 'x-circle'],
            self::STATUS_CANCELLED   => ['label' => 'Cancelled', 'color' => 'danger',  'icon' => 'slash-circle'],
            self::STATUS_FOR_INITIAL_REVIEW  => ['label' => 'For Initial Review',  'color' => 'warning',   'icon' => 'journal-check'],
            self::STATUS_FOR_FINAL_REVIEW    => ['label' => 'For Final Review',    'color' => 'warning',   'icon' => 'file-earmark-check'],

        ];
    }

    public function statusLabel()
    {
        return self::statusLabels()[$this->status] ?? [
            'label' => ucfirst($this->status),
            'color' => 'secondary',
            'icon'  => 'question-circle'
        ];
    }

    public function assetReservations()
    {
        return $this->hasMany(AssetReservation::class, 'booking_id');
    }

public function bookingAgent()
{
    return $this->hasOne(\App\Models\BookingAgent::class, 'booking_id', 'id');
}
public function bookingDetail()
{
    return $this->hasOne(\App\Models\BookingDetail::class, 'booking_id');
}
public function cemetery()
{
    return $this->belongsTo(Cemetery::class, 'cemetery_id');
}

public function serviceLogs()
{
    return $this->hasMany(\App\Models\BookingServiceLog::class, 'booking_id');
}
}
