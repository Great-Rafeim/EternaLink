<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'plot_id',
        'name',
        'contact_info',
        'date_reserved',
        'notes',
        'purpose_of_reservation',
        'address',
        'identification_number',
        'archived_at', // ← Add this
    ];


    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    protected $dates = ['archived_at'];

    // Optional: Scope to exclude archived
    public function scopeActive(Builder $query)
    {
        return $query->whereNull('archived_at');
    }

}
