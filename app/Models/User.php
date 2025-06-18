<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes; // ✅ CORRECT
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;





class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;
    use SoftDeletes;
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function logins()
    {
        return $this->hasMany(\App\Models\Login::class);
    }

    public function servicePackages()
    {
        return $this->hasMany(ServicePackage::class, 'funeral_home_id');
    }

        // Partnership requests sent by this user (as funeral parlor)
    public function sentPartnershipRequests()
    {
        return $this->hasMany(Partnership::class, 'requester_id');
    }

    // Partnership requests received by this user (as funeral parlor)
    public function receivedPartnershipRequests()
    {
        return $this->hasMany(Partnership::class, 'partner_id');
    }

    // All partnerships where this user is involved and accepted
    public function partnerships()
    {
        return Partnership::where(function ($query) {
                $query->where('requester_id', $this->id)
                    ->orWhere('partner_id', $this->id);
            })->where('status', 'accepted');
    }
    public function scopeFuneral($query)
    {
        return $query->where('role', 'funeral');
    }

public function agents()
{
    return $this->belongsToMany(User::class, 'funeral_home_agent', 'funeral_user_id', 'agent_user_id')
        ->where('role', 'agent');
}

// Get all funeral users (parlors) linked to this agent
public function funeralHomes()
{
    return $this->belongsToMany(User::class, 'funeral_home_agent', 'agent_user_id', 'funeral_user_id')
        ->where('role', 'funeral');
}

// For agents
public function assignedClients()
{
    return $this->belongsToMany(User::class, 'agent_client', 'agent_id', 'client_id')
        ->withPivot('case')
        ->withTimestamps();
}

// For clients
public function assignedAgents()
{
    return $this->belongsToMany(User::class, 'agent_client', 'client_id', 'agent_id')
        ->withPivot('case')
        ->withTimestamps();
}
public function bookings()
{
    return $this->hasMany(\App\Models\Booking::class);
}

public function funeralParlor()
{
    return $this->hasOne(\App\Models\FuneralParlor::class);
}

public function createdAssetReservations()
{
    return $this->hasMany(AssetReservation::class, 'created_by');
}


}
