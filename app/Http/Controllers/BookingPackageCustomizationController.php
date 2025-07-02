<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\CustomizedPackage;
use App\Models\CustomizedPackageItem;
use Illuminate\Support\Facades\DB;
use App\Notifications\CustomizationRequestSubmitted;

class BookingPackageCustomizationController extends Controller
{
    // Show customization form
    public function edit($bookingId)
    {
        $booking = Booking::with(['funeralHome', 'package'])->findOrFail($bookingId);
        if ($booking->client_user_id !== auth()->id()) abort(403);

        // Find or create customized package
        $customized = CustomizedPackage::firstOrCreate(
            ['booking_id' => $booking->id],
            ['original_package_id' => $booking->package_id]
        );

        // Pre-fill items if not already added
        if ($customized->items()->count() === 0) {
            foreach ($booking->package->items as $pkgItem) {
                CustomizedPackageItem::create([
                    'customized_package_id' => $customized->id,
                    'inventory_item_id'     => $pkgItem->id,
                    'quantity'              => $pkgItem->pivot->quantity,
                    'unit_price'            => $pkgItem->selling_price ?? $pkgItem->price ?? 0,
                ]);
            }
        }

        $items = $customized->items()->with(['item', 'substitutedOriginal'])->get();

        $inventory = InventoryItem::where('funeral_home_id', $booking->funeral_home_id)
            ->where('status', 'available')
            ->where('quantity', '>', 0)
            ->with('category')
            ->get()
            ->groupBy('inventory_category_id');

        return view('client.bookings.package-customization.edit', compact('booking', 'customized', 'items', 'inventory'));
    }

    // Update customization (if allowed)
    public function update(Request $request, $bookingId)
    {
        $booking = Booking::with('funeralHome')->findOrFail($bookingId);
        if ($booking->client_user_id !== auth()->id()) abort(403);

        $customized = CustomizedPackage::where('booking_id', $booking->id)->firstOrFail();

        if (!in_array($customized->status, ['draft', 'denied'])) {
            return back()->with('error', 'You cannot edit while your customization request is pending or approved.');
        }

        $input = $request->input('custom_items', []);
        DB::beginTransaction();

        try {
            $customized->items()->delete();

            $totalPrice = 0;

            foreach ($booking->package->items as $item) {
                $data = $input[$item->id] ?? [];

                $substituteId = $data['substitute_for'] ?? $item->id;
                $quantity = max(1, intval($data['quantity'] ?? $item->pivot->quantity));

                $inventoryItem = InventoryItem::findOrFail($substituteId);

                if ($quantity > $inventoryItem->quantity) {
                    throw new \Exception("Requested quantity ({$quantity}) exceeds stock ({$inventoryItem->quantity}) for {$inventoryItem->name}.");
                }

                CustomizedPackageItem::create([
                    'customized_package_id' => $customized->id,
                    'inventory_item_id'     => $substituteId,
                    'substitute_for'        => $substituteId == $item->id ? null : $item->id,
                    'quantity'              => $quantity,
                    'unit_price'            => $inventoryItem->selling_price ?? 0,
                ]);

                $totalPrice += $quantity * ($inventoryItem->selling_price ?? 0);
            }

            $customized->custom_total_price = $totalPrice;
            $customized->save();

            DB::commit();

            return back()->with('success', 'Customization saved. Click "Send Customization Request" to submit for parlor approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['customization_error' => $e->getMessage()])->withInput();
        }
    }

    // Send customization request for approval
// Send customization request for approval
public function sendRequest(Request $request, $bookingId)
{
    $booking = Booking::with(['funeralHome', 'package', 'bookingAgent.agentUser', 'client'])->findOrFail($bookingId);
    if ($booking->client_user_id !== auth()->id()) abort(403);

    $customized = CustomizedPackage::firstOrCreate(
        ['booking_id' => $booking->id],
        ['original_package_id' => $booking->package_id]
    );

    $input = $request->input('custom_items', []);
    \DB::beginTransaction();

    try {
        // Wipe old customization
        $customized->items()->delete();

        $totalPrice = 0;

        foreach ($booking->package->items as $item) {
            $data = $input[$item->id] ?? [];

            $substituteId = $data['substitute_for'] ?? $item->id;
            $quantity = max(1, intval($data['quantity'] ?? $item->pivot->quantity));

            $inventoryItem = \App\Models\InventoryItem::findOrFail($substituteId);

            if ($quantity > $inventoryItem->quantity) {
                throw new \Exception("Quantity ({$quantity}) exceeds stock ({$inventoryItem->quantity}) for {$inventoryItem->name}.");
            }

            \App\Models\CustomizedPackageItem::create([
                'customized_package_id' => $customized->id,
                'inventory_item_id'     => $substituteId,
                'substitute_for'        => $substituteId == $item->id ? null : $item->id,
                'quantity'              => $quantity,
                'unit_price'            => $inventoryItem->selling_price ?? 0,
            ]);

            $totalPrice += $quantity * ($inventoryItem->selling_price ?? 0);
        }

        $customized->custom_total_price = $totalPrice;
        $customized->status = 'pending';
        $customized->save();

        // Notify funeral parlor
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $booking->client->name ?? 'the client';
        $packageName = $booking->package->name ?? 'the package';

        $parlorMsg = "A customization request for booking <b>#{$booking->id}</b> has been submitted by <b>{$clientName}</b>. Please review and take action.";
        $booking->funeralHome->notify(
            new \App\Notifications\CustomizationRequestSubmitted($customized, $parlorMsg, 'funeral')
        );

        // Notify agent (if any)
        if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
            $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
            $agentMsg = "A customization request for booking <b>#{$booking->id}</b> assigned to your client (<b>{$clientName}</b>) at <b>{$parlorName}</b> has been submitted and is pending parlor review.";
            if ($agentUser) {
                $agentUser->notify(
                    new \App\Notifications\CustomizationRequestSubmitted($customized, $agentMsg, 'agent')
                );
            }
        }

        \DB::commit();
        return back()->with('success', 'Customization request sent. Awaiting parlor approval.');
    } catch (\Exception $e) {
        \DB::rollBack();
        return back()->withErrors(['customization_error' => $e->getMessage()]);
    }
}


}
