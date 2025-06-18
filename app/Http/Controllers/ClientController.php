<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\FuneralParlor;
use App\Http\Controllers\Controller;

class ClientController extends Controller
{
    // List all funeral parlors
    public function parlors(Request $request)
    {
        $query = User::with('funeralParlor')
            ->where('role', 'funeral');

        // Name fuzzy search
        if ($request->filled('q')) {
            $search = $request->input('q');
            $chunks = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function($q) use ($chunks) {
                foreach ($chunks as $chunk) {
                    $q->orWhere('name', 'like', '%' . $chunk . '%');
                }
            });
        }

        // Address fuzzy search
        if ($request->filled('address')) {
            $address = $request->input('address');
            $addrChunks = preg_split('/\s+/', $address, -1, PREG_SPLIT_NO_EMPTY);

            $query->whereHas('funeralParlor', function($q) use ($addrChunks) {
                foreach ($addrChunks as $chunk) {
                    $q->orWhere('address', 'like', '%' . $chunk . '%');
                }
            });
        }

        $parlors = $query->paginate(9)->appends($request->only('q', 'address'));

        return view('client.parlors.index', compact('parlors'));
    }


    // Show a single funeral parlor with its service packages
    public function showServicePackages($id)
    {
        $parlor = User::with('funeralParlor')
            ->where('role', 'funeral')
            ->findOrFail($id);

        $servicePackages = \App\Models\ServicePackage::where('funeral_home_id', $parlor->id)
            ->orderBy('name')
            ->get();

        return view('client.parlors.service_packages', compact('parlor', 'servicePackages'));
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

        // Store data to update
        $parlorData = [
            'address'        => $request->address,
            'contact_email'  => $request->contact_email,
            'contact_number' => $request->contact_number,
            'description'    => $request->description,
        ];

        // Remove image if requested
        if ($request->input('remove_image') == "1" && $parlor && $parlor->image) {
            \Storage::disk('public')->delete($parlor->image);
            $parlorData['image'] = null; // <--- Key difference: use update array!
        }

        // Upload new image if present (replaces old image)
        if ($request->hasFile('image')) {
            // Delete the old image if any
            if ($parlor && $parlor->image) {
                \Storage::disk('public')->delete($parlor->image);
            }
            $parlorData['image'] = $request->file('image')->store('parlors', 'public');
        }

        // Save all fields, including image (null or new)
        if ($parlor) {
            $parlor->update($parlorData);
        }

        // Update user name if changed
        $user->name = $request->name;
        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }


}
