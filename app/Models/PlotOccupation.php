<?php 
// app/Models/PlotOccupation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlotOccupation extends Model
{
    protected $fillable = [
        'plot_id',
        'booking_id',
        'deceased_first_name',
        'deceased_middle_name',
        'deceased_last_name',
        'deceased_nickname',
        'deceased_sex',
        'deceased_birthday',
        'deceased_date_of_death',
        'deceased_age',
        'deceased_civil_status',
        'deceased_residence',
        'deceased_citizenship',
        'remarks',
    ];

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    public function booking()
    {
        return $this->belongsTo(CemeteryBooking::class, 'booking_id');
    }


}
