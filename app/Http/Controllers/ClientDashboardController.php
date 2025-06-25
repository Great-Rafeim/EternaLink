<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\BookingServiceLog;

class ClientDashboardController extends Controller
{
public function index() 
{
    $bookings = \App\Models\Booking::with([
        'package',
        'funeralHome',
        'bookingAgent.agentUser', // Correct!
    ])
    ->where('client_user_id', auth()->id())
    ->orderByDesc('created_at')
    ->paginate(10);

    return view('client.dashboard', compact('bookings'));
}



    // Booking detail view
public function show($id)
{
    $booking = Booking::with([
        'package.items.category',
        'funeralHome',
        'bookingAgent.agentUser', // Eager load agentUser from bookingAgent
    ])
    ->where('client_user_id', Auth::id())
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

    return view('client.bookings.show', compact('booking', 'packageItems', 'assetCategories', 'serviceLogs'));
}




    public function cancel($bookingId)
    {
        $booking = Booking::where('client_user_id', auth()->id())->findOrFail($bookingId);

        if (!in_array($booking->status, ['pending', 'confirmed', 'assigned'])) {
            return back()->with('error', 'This booking cannot be canceled.');
        }

        $booking->status = 'canceled';
        $booking->save();

        return back()->with('success', 'Your booking has been canceled.');
    }


}

