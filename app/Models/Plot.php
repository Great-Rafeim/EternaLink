<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plot extends Model
{
    use HasFactory;

    // The attributes that are mass assignable
    protected $fillable = [
        'cemetery_id',
        'plot_number',
        'section',
        'block',
        'type',
        'status',
        'owner_id',
        'deceased_name',
        'birth_date',
        'death_date',
    ];

    // Cast dates properly
    protected $dates = [
        'birth_date',
        'death_date',
        'created_at',
        'updated_at',
    ];

    public function reservation()
    {
        return $this->hasOne(Reservation::class)->whereNull('archived_at');
    }

    public function occupation()
    {
        return $this->hasOne(PlotOccupation::class)->whereNull('archived_at');
    }

    public function reservationHistory()
    {
        return $this->hasMany(Reservation::class)->whereNotNull('archived_at');
    }

    public function occupationHistory()
    {
        return $this->hasMany(PlotOccupation::class)->whereNotNull('archived_at');
    }

    public function cemetery()
    {
        return $this->belongsTo(Cemetery::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
