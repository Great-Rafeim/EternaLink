<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuneralParlor extends Model
{
    //

    protected $fillable = [
        'user_id',
        'address',
        'contact_email',
        'contact_number',
        'description',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    
    public function parlors()
    {
        $parlors = User::with('funeralParlor')
            ->where('role', 'funeral')
            ->paginate(9);

        return view('client.parlors.index', compact('parlors'));
    }

}
