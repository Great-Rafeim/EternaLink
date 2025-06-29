<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'name',
        'contact_info',
        'relationship',
        'deceased_name',
        'deceased_dob',
        'deceased_dod',
        'notes',
    ];

}

