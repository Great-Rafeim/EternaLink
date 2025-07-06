<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class webhookModel extends Model
{
    protected $table = 'webhook';

    protected $fillable = [
        'test',
        'payload',
    ];

    use HasFactory;
}
