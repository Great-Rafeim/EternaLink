<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlotOccupation extends Model
{
    protected $fillable = [
        'plot_id',
        'deceased_name',
        'birth_date',
        'death_date',
        'burial_date',
        'cause_of_death',
        'funeral_home',
        'next_of_kin_name',
        'next_of_kin_contact',
        'interred_by',
        'notes',
        'archived_at', // ← Add this
    ];


    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'burial_date' => 'date',
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
