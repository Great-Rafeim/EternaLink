<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\BookingServiceLog;

class ClientDashboardController extends Controller
{
    public function index() 
    {
        //for funeral bookings
        $bookings = \App\Models\Booking::with([
            'package',
            'funeralHome',
            'bookingAgent.agentUser', // Correct!
        ])
        ->where('client_user_id', auth()->id())
        ->orderByDesc('created_at')
        ->paginate(10);

            // Cemetery bookings for this client, with full relation to show cemetery name (via its user)
        $cemeteryBookings = \App\Models\CemeteryBooking::with([
                'cemetery.user', // This gives you access to $cemeteryBooking->cemetery->user->name
            ])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('client.dashboard', compact('bookings', 'cemeteryBookings'));
    }



public function show($id)
{
    $booking = \App\Models\Booking::with([
            'package.items.category',
            'funeralHome',
            'bookingAgent.agentUser',
            // Eager load cemetery relationships:
            'cemeteryBooking.cemetery.user',
            'cemeteryBooking.plot',
        ])
        ->where('client_user_id', \Auth::id())
        ->findOrFail($id);

    // Prepare the package items array
    $packageItems = $booking->package->items->map(function($item) {
        return [
            'item'       => $item->name,
            'category'   => $item->category->name ?? '-',
            'brand'      => $item->brand ?? '-',
            'quantity'   => $item->pivot->quantity ?? 1,
            'category_id'=> $item->category->id ?? null,
            'is_asset'   => ($item->category->is_asset ?? false) ? true : false,
        ];
    })->toArray();

    // Get all asset categories linked to this package
    $assetCategories = \DB::table('package_asset_categories')
        ->join('inventory_categories', 'package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
        ->where('package_asset_categories.service_package_id', $booking->package->id)
        ->where('inventory_categories.is_asset', 1)
        ->select('inventory_categories.id', 'inventory_categories.name')
        ->get();

    // Load service logs (order by most recent)
    $serviceLogs = \App\Models\BookingServiceLog::with('user')
        ->where('booking_id', $booking->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Find assigned plot via booking_details
    $bookingDetail = \App\Models\BookingDetail::where('booking_id', $booking->id)->first();
    $plot = null;
    $plotCemetery = null;
    $cemeteryOwner = null;

    if ($bookingDetail && $bookingDetail->plot_id) {
        $plot = \App\Models\Plot::with('cemetery.user')->find($bookingDetail->plot_id);
        if ($plot) {
            $plotCemetery = $plot->cemetery;         // The Cemetery model
            $cemeteryOwner = $plotCemetery?->user;   // The User model (cemetery owner)
        }
    }

    // Now you can use $booking->cemeteryBooking and $plot, $plotCemetery, $cemeteryOwner in your Blade
    return view('client.bookings.show', compact(
        'booking',
        'packageItems',
        'assetCategories',
        'serviceLogs',
        'plot',
        'plotCemetery',
        'cemeteryOwner'
    ));
}






    public function cancel($bookingId)
    {
        $booking = Booking::where('client_user_id', auth()->id())->findOrFail($bookingId);

        if (!in_array($booking->status, ['pending', 'confirmed', 'assigned'])) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $booking->status = 'cancelled';
        $booking->save();

        return back()->with('success', 'Your booking has been cancelled.');
    }
    
    public function update(Request $request, $id)
    {
        $cemetery = \App\Models\Cemetery::findOrFail($id);

        $request->validate([
            'address' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        // Handle image upload
        if ($request->has('remove_image') && $request->remove_image == '1') {
            if ($cemetery->image_path && \Storage::disk('public')->exists($cemetery->image_path)) {
                \Storage::disk('public')->delete($cemetery->image_path);
            }
            $cemetery->image_path = null;
        }
        if ($request->hasFile('image')) {
            if ($cemetery->image_path && \Storage::disk('public')->exists($cemetery->image_path)) {
                \Storage::disk('public')->delete($cemetery->image_path);
            }
            $cemetery->image_path = $request->file('image')->store('cemetery_images', 'public');
        }

        $cemetery->update($request->only('address', 'contact_number', 'description'));

        $cemetery->save();

        return redirect()->route('cemetery.edit', $cemetery->id)
            ->with('success', 'Cemetery info updated successfully!');
    }


}

