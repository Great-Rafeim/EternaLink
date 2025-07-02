<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FuneralParlor;
use App\Http\Controllers\Controller;

class FuneralParlorController extends Controller{

    public function editProfile()
    {
        $user = Auth::user();
        $parlor = $user->funeralParlor; // relationship from User to FuneralParlor

        return view('funeral.profile.edit', compact('user', 'parlor'));
    }

public function updateProfile(Request $request)
{
    $user = Auth::user();
    $parlor = $user->funeralParlor;

    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'address' => ['nullable', 'string', 'max:255'],
        'contact_email' => ['nullable', 'email', 'max:255'],
        'contact_number' => ['nullable', 'string', 'max:50'],
        'description' => ['nullable', 'string'],
        'image' => ['nullable', 'image', 'max:20480'],
    ]);

    // Gather parlor data
    $parlorData = [
        'address'        => $request->address,
        'contact_email'  => $request->contact_email,
        'contact_number' => $request->contact_number,
        'description'    => $request->description,
        'user_id'        => $user->id, // Make sure to link!
    ];

    // Remove image if requested
    if ($request->input('remove_image') == "1" && $parlor && $parlor->image) {
        \Storage::disk('public')->delete($parlor->image);
        $parlorData['image'] = null;
    }

    // Handle new image upload
    if ($request->hasFile('image')) {
        if ($parlor && $parlor->image) {
            \Storage::disk('public')->delete($parlor->image);
        }
        $parlorData['image'] = $request->file('image')->store('parlors', 'public');
    }

    // Create or update the parlor
    if ($parlor) {
        $parlor->update($parlorData);
    } else {
        FuneralParlor::create($parlorData);
    }

    // Update user name if changed
    $user->name = $request->name;
    $user->save();

    return redirect()->back()->with('success', 'Profile updated successfully!');
}


    
}