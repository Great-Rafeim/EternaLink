<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\ServicePackage;
use App\Models\Booking;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingStatusChanged;
use Illuminate\Support\Facades\DB;
use App\Models\AssetReservation;
use Carbon\Carbon;

class FuneralDashboardController extends Controller
{
    // 1. DASHBOARD
    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        return view('funeral.dashboard', [
            'totalItems' => InventoryItem::where('funeral_home_id', $userId)->count(),
            'lowStockCount' => InventoryItem::where('funeral_home_id', $userId)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->count(),
            'categoryCount' => InventoryCategory::where('funeral_home_id', $userId)->count(),
            'packageCount' => ServicePackage::where('funeral_home_id', $userId)->count(),
            'recentNotifications' => $user->notifications()->latest()->paginate(5),
        ]);
    }

    // 2. BOOKINGS MANAGEMENT (Grouped by status)
    public function bookings()
    {
        $funeralHomeId = auth()->id();

        // New = pending or assigned (awaiting initial review)
        $newBookings = Booking::with(['client', 'agent', 'package'])
            ->where('funeral_home_id', $funeralHomeId)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED
            ])
            ->orderByDesc('created_at')
            ->get();
        $forInitialReviewBookings = Booking::with(['client', 'agent', 'package'])
            ->where('funeral_home_id', $funeralHomeId)
            ->where('status', Booking::STATUS_FOR_INITIAL_REVIEW)
            ->orderByDesc('updated_at')
            ->get();
        // Client is filling out booking forms
        $inProgressBookings = Booking::with(['client', 'agent', 'package'])
            ->where('funeral_home_id', $funeralHomeId)
            ->where('status', Booking::STATUS_IN_PROGRESS)
            ->orderByDesc('updated_at')
            ->get();

        // Client submitted all booking forms, pending parlor review
        $readyForReviewBookings = Booking::with(['client', 'agent', 'package', 'detail'])
            ->where('funeral_home_id', $funeralHomeId)
            ->where('status', Booking::STATUS_SUBMITTED)
            ->orderByDesc('updated_at')
            ->get();

        // All details approved by parlor, awaiting start
        $approvedBookings = Booking::with(['client', 'agent', 'package'])
            ->where('funeral_home_id', $funeralHomeId)
            ->where('status', Booking::STATUS_APPROVED)
            ->orderByDesc('updated_at')
            ->get();
        // Ongoing service
        $ongoingBookings = Booking::with(['client', 'agent', 'package'])
            ->where('funeral_home_id', $funeralHomeId)
            ->where('status', Booking::STATUS_ONGOING)
            ->orderByDesc('updated_at')
            ->get();

        // Done = completed
        $doneBookings = Booking::with(['client', 'agent', 'package'])
            ->where('funeral_home_id', $funeralHomeId)
            ->where('status', Booking::STATUS_COMPLETED)
            ->orderByDesc('updated_at')
            ->get();

        // Customization requests (pending only)
$customizationRequests = Booking::with(['client', 'package', 'customizationRequests' => function($q) {
    $q->where('status', 'pending');
}])
->where('funeral_home_id', $funeralHomeId)
->whereHas('customizationRequests', fn($q) => $q->where('status', 'pending'))
->orderByDesc('updated_at')
->get();


        return view('funeral.bookings.index', compact(
            'newBookings',
            'inProgressBookings',
            'forInitialReviewBookings',
            'readyForReviewBookings',
            'approvedBookings',
            'ongoingBookings',
            'doneBookings',
            'customizationRequests'
        ));
    }

    // 3. SHOW SINGLE BOOKING (optional)
    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        return view('funeral.bookings.show', compact('booking'));
    }



public function approve(Request $request, Booking $booking)
{
    $this->authorizeBooking($booking);

    // Determine new status
    if ($booking->status === Booking::STATUS_PENDING) {
        $newStatus = Booking::STATUS_CONFIRMED;
    } elseif ($booking->status === Booking::STATUS_SUBMITTED || $booking->status === 'for_review') {
        $newStatus = Booking::STATUS_APPROVED;
    } else {
        return back()->with('error', 'Booking cannot be approved at this stage.');
    }

    try {
        DB::transaction(function () use ($booking, $newStatus) {
            // ---- Determine Reservation Window ----
            $detail = $booking->detail;
            $start = now();
            $end = now()->addDays(3);

            if ($detail && $detail->wake_start_date) {
                $start = \Carbon\Carbon::parse($detail->wake_start_date)->startOfDay();
                if (!empty($detail->burial_date)) {
                    $end = \Carbon\Carbon::parse($detail->burial_date)->endOfDay();
                } elseif (!empty($detail->wake_end_date)) {
                    $end = \Carbon\Carbon::parse($detail->wake_end_date)->endOfDay();
                }
            }

            // ---- Collect Items: customized or standard ----
            $items = $booking->customized_package_id && $booking->customizedPackage
                ? $booking->customizedPackage->items
                : $booking->package->items;

            foreach ($items as $item) {
                $invItem = $item->inventoryItem ?? $item;
                $qtyToDeduct = $item->quantity ?? ($item->pivot->quantity ?? 1);

                // Always eager load category
                $category = $invItem->category ?? $invItem->load('category')->category;

                // --- Bookable Asset Handling ---
                if ($category && $category->is_asset) {
                    // Check reservation conflict
                    $conflict = \App\Models\AssetReservation::where('inventory_item_id', $invItem->id)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($q) use ($start, $end) {
                            $q->whereBetween('reserved_start', [$start, $end])
                                ->orWhereBetween('reserved_end', [$start, $end])
                                ->orWhere(function($sub) use ($start, $end) {
                                    $sub->where('reserved_start', '<=', $start)
                                        ->where('reserved_end', '>=', $end);
                                });
                        })
                        ->exists();

                    if ($conflict) {
                        throw new \Exception("Asset '{$invItem->name}' is already reserved for another booking during this period.");
                    }

                    // Create asset reservation
                    \App\Models\AssetReservation::create([
                        'inventory_item_id' => $invItem->id,
                        'booking_id'        => $booking->id,
                        'reserved_start'    => $start,
                        'reserved_end'      => $end,
                        'status'            => 'reserved',
                        'created_by'        => auth()->id(),
                    ]);

                    // Mark asset as reserved
                    $invItem->status = 'reserved';
                    $invItem->save();

                    continue; // Asset: Do not deduct quantity
                }

                // --- Consumable/Shareable Handling ---
                if ($invItem->quantity < $qtyToDeduct) {
                    throw new \Exception("Not enough stock for '{$invItem->name}'. Available: {$invItem->quantity}, Required: $qtyToDeduct");
                }

                // Deduct main quantity
                $invItem->quantity -= $qtyToDeduct;

                // Deduct shareable if enabled and present
                if ($invItem->shareable && !is_null($invItem->shareable_quantity) && $invItem->shareable_quantity > 0) {
                    $invItem->shareable_quantity = max(0, $invItem->shareable_quantity - min($qtyToDeduct, $invItem->shareable_quantity));
                }

                $invItem->save();
            }

            $booking->status = $newStatus;
            $booking->save();
        });

    } catch (\Exception $e) {
        return back()->withInput()->with('error', $e->getMessage());
    }

    // ==== Notification Block ====
    $msg = ($newStatus === Booking::STATUS_CONFIRMED)
        ? "Your booking for <b>{$booking->package->name}</b> has been <b>PRE-APPROVED</b>. Please proceed with filling out the required information."
        : "Your booking for <b>{$booking->package->name}</b> has been <b>APPROVED</b> and is now ready to start.";

    // Always prepare the asset list (so it's available for notification, even if status is not APPROVED yet)
    $assets = $booking->customized_package_id && $booking->customizedPackage
        ? $booking->customizedPackage->items->filter(fn($i) => ($i->inventoryItem?->category?->is_asset ?? false))
        : $booking->package->items->filter(fn($i) => ($i->category?->is_asset ?? false));

    if ($newStatus === Booking::STATUS_APPROVED && $assets->count()) {
        $assetDetails = $assets->map(function($i) {
            $inv = $i->inventoryItem ?? $i;
            $catName = $inv->category && $inv->category->name ? $inv->category->name : 'Asset';
            return "- " . ($inv->name ?? 'Unknown') . " ({$catName})";
        })->implode('<br>');
        $msg .= "<br><b>Reserved Asset(s):</b><br>" . $assetDetails;
    }

    if ($booking->client) $booking->client->notify(new BookingStatusChanged($booking, $msg));
    if ($booking->agent) $booking->agent->notify(new BookingStatusChanged($booking, $msg));

    return redirect()->route('funeral.bookings.index')
        ->with('success', 'Booking approved, inventory updated, and assets reserved.');
}
    // 5. DENY BOOKING
    public function deny(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status === Booking::STATUS_DECLINED) {
            logger()->warning("Booking {$booking->id} is already declined.");
            return back()->with('error', 'Booking already declined.');
        }

        $booking->status = Booking::STATUS_DECLINED;
        $booking->save();

        $message = "Your booking for <b>{$booking->package->name}</b> has been <b>DECLINED</b>.";
        if ($booking->client) {
            $booking->client->notify(new BookingStatusChanged($booking, $message));
        }
        if ($booking->agent) {
            $booking->agent->notify(new BookingStatusChanged($booking, "A booking you are handling was DECLINED."));
        }

        return redirect()->route('funeral.bookings.index')->with('success', 'Booking declined.');
    }


public function accept($bookingId)
{
    $booking = Booking::findOrFail($bookingId);
    // Authorize funeral home
    if ($booking->funeral_home_id !== auth()->id()) abort(403);

    $booking->status = 'confirmed';
    $booking->save();

    // Notify client
    if ($booking->client) {
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $bookingNumber = $booking->id;
        $msg = "Your booking #{$bookingNumber} at {$parlorName} has been accepted. Please fill up the required forms";
        $booking->client->notify(
            new \App\Notifications\BookingStatusChanged($booking, $msg)
        );
    }

    return back()->with('success', 'Booking accepted.');
}

public function reject($bookingId)
{
    $booking = Booking::findOrFail($bookingId);
    if ($booking->funeral_home_id !== auth()->id()) abort(403);

    $booking->status = 'declined';
    $booking->save();

    // Notify client
    if ($booking->client) {
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $bookingNumber = $booking->id;
        $msg = "Your booking #{$bookingNumber} at {$parlorName} has been rejected. Please contact us for further assistance.";
        $booking->client->notify(
            new \App\Notifications\BookingStatusChanged($booking, $msg)
        );
    }

    return back()->with('success', 'Booking has been rejected.');
}



    // FINAL APPROVAL: submitted ➔ approved
    public function finalApprove(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== Booking::STATUS_SUBMITTED) {
            return back()->with('error', 'Booking cannot be approved at this stage.');
        }

        $booking->status = Booking::STATUS_APPROVED;
        $booking->save();

        // Notify client and agent
        $message = "Your booking for <b>{$booking->package->name}</b> has been <b>APPROVED</b> and is now ready to start.";
        if ($booking->client) {
            $booking->client->notify(new BookingStatusChanged($booking, $message));
        }
        if ($booking->agent) {
            $booking->agent->notify(new BookingStatusChanged($booking, "A booking you are handling was FULLY APPROVED."));
        }

        return redirect()->route('funeral.bookings.index')->with('success', 'Booking fully approved and ready to start.');
    }

        // START SERVICE: approved ➔ ongoing
    public function startService(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== Booking::STATUS_APPROVED) {
            return back()->with('error', 'Booking is not yet ready to start service.');
        }

        $booking->status = Booking::STATUS_ONGOING;
        $booking->save();

        return back()->with('success', 'Service started.');
    }

    // MARK AS COMPLETED: ongoing ➔ completed
    public function markCompleted(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== Booking::STATUS_ONGOING) {
            return back()->with('error', 'Service not ongoing.');
        }

        $booking->status = Booking::STATUS_COMPLETED;
        $booking->save();

        return back()->with('success', 'Booking marked as completed.');
    }


    /**
     * Ensures that the booking belongs to the currently authenticated funeral home user.
     */
    protected function authorizeBooking(Booking $booking)
    {
        if ($booking->funeral_home_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }
    }

    // Show customization request details
    public function customizationShow($bookingId, $customizedPackageId)
    {
        // Booking with relationship checks for auth
        $booking = Booking::with(['client', 'package'])
            ->where('funeral_home_id', auth()->id())
            ->findOrFail($bookingId);

        // Find the specific customization request for this booking
        $customizedPackage = \App\Models\CustomizedPackage::with([
                'items.inventoryItem',
                'items.substituteFor'
            ])
            ->where('booking_id', $bookingId)
            ->findOrFail($customizedPackageId);

        return view('funeral.bookings.customization.show', compact('booking', 'customizedPackage'));
    }



    // REVIEW DETAILS - For ready-for-review (after info of the dead)
    public function reviewDetails(Booking $booking)
    {
        if ($booking->funeral_home_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }
        $booking->load(['client', 'agent', 'package', 'detail']);
        return view('funeral.bookings.review-details', compact('booking'));
    }

    // Approve customization request
 // Approve customization request
public function customizationApprove(Request $request, $bookingId, $customizedPackageId)
{
    $booking = Booking::where('funeral_home_id', auth()->id())->findOrFail($bookingId);

    $customized = \App\Models\CustomizedPackage::where('id', $customizedPackageId)
        ->where('booking_id', $booking->id)
        ->where('status', 'pending')
        ->first();

    if (!$customized) {
        return back()->with('error', 'No pending customization request to approve.');
    }

    $customized->status = 'approved';
    $customized->save();

    $booking->customized_package_id = $customized->id;
    $booking->save();

    $booking->client->notify(new \App\Notifications\CustomizationRequestApproved($booking, $customized));

    return back()->with('success', 'Customization approved and client notified.');
}

// Deny customization request
public function customizationDeny(Request $request, $bookingId, $customizedPackageId)
{
    $booking = Booking::where('funeral_home_id', auth()->id())->findOrFail($bookingId);

    $customized = \App\Models\CustomizedPackage::where('id', $customizedPackageId)
        ->where('booking_id', $booking->id)
        ->where('status', 'pending')
        ->first();

    if (!$customized) {
        return back()->with('error', 'No pending customization request to deny.');
    }

    $customized->status = 'denied';
    $customized->save();

    // Optionally: revert to original items if needed

    $booking->client->notify(new \App\Notifications\CustomizationRequestDenied($booking, $customized));

    return back()->with('success', 'Customization denied. Client notified.');
}

// FuneralDashboardController.php

public function updateOtherFees(Request $request, Booking $booking)
{
    // Authorization: Only funeral parlor can update, and only at the correct status
    if ($booking->funeral_home_id !== auth()->id()) {
        abort(403, 'Unauthorized.');
    }

    if ($booking->status !== Booking::STATUS_FOR_INITIAL_REVIEW) {
        return back()->with('error', 'You can only set other fees during initial review phase.');
    }

    $validated = $request->validate([
        'other_fee' => ['required', 'numeric', 'min:0'],
    ]);

    // Ensure detail exists
    $detail = $booking->detail ?: new \App\Models\BookingDetail(['booking_id' => $booking->id]);
    $detail->other_fee = $validated['other_fee'];
    $detail->save();

    // --- CALCULATE FINAL AMOUNT ---
    $customized = $booking->customizedPackage;
    if ($customized && $customized->status === 'approved') {
        $packageTotal = $customized->custom_total_price;
    } else {
        $packageTotal = $booking->package->items->sum(function($item) {
            return $item->pivot->quantity * ($item->selling_price ?? $item->price ?? 0);
        });
    }

    $finalAmount = $packageTotal + ($detail->other_fee ?? 0);
    $booking->final_amount = $finalAmount;

    // Update booking status
    $booking->status = Booking::STATUS_IN_PROGRESS;
    $booking->save();

    // Notify the client
    if ($booking->client) {
        $msg = "The funeral parlor has set additional fees for your booking. Please proceed to fill in the deceased's personal details.";
        $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $msg));
    }

    return redirect()
        ->route('funeral.bookings.show', $booking->id)
        ->with('success', 'Other fees updated and client notified. Booking is now in progress.');
}

public function updatePaymentRemarks(Request $request, Booking $booking)
{
    if ($booking->funeral_home_id !== auth()->id()) abort(403, 'Unauthorized.');

    $validated = $request->validate([
        'remarks' => ['nullable', 'string', 'max:255'],
    ]);

    $detail = $booking->detail ?: new \App\Models\BookingDetail(['booking_id' => $booking->id]);
    $detail->remarks = $validated['remarks'];
    $detail->save();

    // Notify client
    if ($booking->client) {
        $msg = "The funeral parlor has added or updated payment remarks for your booking.";
        $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $msg));
    }

    return back()->with('success', 'Payment remarks updated.');
}




}
