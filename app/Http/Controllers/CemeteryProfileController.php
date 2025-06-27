<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Cemetery;

class CemeteryProfileController extends Controller
{
    /**
     * Show the edit form for the cemetery profile.
     */
public function edit()
{
    $user = auth()->user();

    $cemetery = \App\Models\Cemetery::firstOrCreate(
        ['user_id' => $user->id],
        [
            'address' => '',
            'contact_number' => '',
            'description' => '',
        ]
    );
    $cemetery->load('user');

    return view('cemetery.profile.edit', compact('cemetery'));
}

    /**
     * Update the cemetery profile.
     */
    public function update(Request $request, $id)
    {
        \Log::info('[DEBUG] Entered CemeteryProfileController@update', ['id' => $id]);
        $cemetery = Cemetery::find($id);

        if (!$cemetery) {
            \Log::error('[DEBUG] No cemetery found for update', ['id' => $id]);
            abort(404, 'Cemetery profile not found.');
        } else {
            \Log::info('[DEBUG] Cemetery found for update:', ['cemetery_id' => $cemetery->id]);
        }

        $validated = $request->validate([
            'address'        => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|max:2048', // 2MB max
        ]);
        \Log::info('[DEBUG] Validated request data', $validated);

        // Handle image removal
        if ($request->has('remove_image') && $request->remove_image == '1') {
            \Log::info('[DEBUG] Removing cemetery image', ['image_path' => $cemetery->image_path]);
            if ($cemetery->image_path && Storage::disk('public')->exists($cemetery->image_path)) {
                Storage::disk('public')->delete($cemetery->image_path);
            }
            $cemetery->image_path = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            \Log::info('[DEBUG] Uploading new image');
            if ($cemetery->image_path && Storage::disk('public')->exists($cemetery->image_path)) {
                Storage::disk('public')->delete($cemetery->image_path);
            }
            $cemetery->image_path = $request->file('image')->store('cemetery_images', 'public');
        }

        $cemetery->address = $request->address;
        $cemetery->contact_number = $request->contact_number;
        $cemetery->description = $request->description;
        $cemetery->save();

        \Log::info('[DEBUG] Cemetery updated successfully', ['cemetery_id' => $cemetery->id]);

        return redirect()->route('cemetery.profile.edit')
            ->with('success', 'Cemetery info updated successfully!');
    }
}
