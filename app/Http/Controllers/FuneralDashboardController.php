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
use Illuminate\Support\Facades\Http;


class FuneralDashboardController extends Controller
{

public function showBooking($id)
{
    $booking = Booking::findOrFail($id);
    return view('funeral.bookings.showBookingRequest', compact('booking'));
}


// 1. DASHBOARD
public function index()
{
    $user = auth()->user();
    $userId = $user->id;

    // Bookings by status (for "pending" use multiple statuses)
    $pendingStatuses = [
        'pending', 
        'for_initial_review', 
        'for_review', 
        'confirmed', 
        'approved', 
        'in_progress'
    ];
    $pendingCount   = \App\Models\Booking::where('funeral_home_id', $userId)
        ->whereIn('status', $pendingStatuses)
        ->count();

    $ongoingCount   = \App\Models\Booking::where('funeral_home_id', $userId)
        ->where('status', 'ongoing')
        ->count();

    $completedCount = \App\Models\Booking::where('funeral_home_id', $userId)
        ->where('status', 'completed')
        ->count();

    // Partners (accepted only, both as requester and partner)
    $partnerCount = \DB::table('partnerships')
        ->where(function($q) use ($userId) {
            $q->where('requester_id', $userId)
              ->orWhere('partner_id', $userId);
        })
        ->where('status', 'accepted')
        ->count();

    // Active agents assigned to this funeral home
    $agentCount = \DB::table('funeral_home_agent')
        ->where('funeral_user_id', $userId)
        ->where('status', 'active')
        ->count();

    // Eager load the assigned agent for each booking (pagination for bookings list below cards)
    $bookings = \App\Models\Booking::with([
            'bookingAgent.agentUser',
        ])
        ->where('funeral_home_id', $userId)
        ->orderByDesc('created_at')
        ->paginate(10);
    
    return view('funeral.dashboard', [
        'bookings' => $bookings,
        'totalItems' => \App\Models\InventoryItem::where('funeral_home_id', $userId)->count(),
        'lowStockCount' => \App\Models\InventoryItem::where('funeral_home_id', $userId)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->count(),
        'categoryCount' => \App\Models\InventoryCategory::where('funeral_home_id', $userId)->count(),
        'packageCount' => \App\Models\ServicePackage::where('funeral_home_id', $userId)->count(),
        'recentNotifications' => $user->notifications()->latest()->paginate(5),
        // Add new summary counts:
        'partnerCount' => $partnerCount,
        'agentCount' => $agentCount,
        'pendingCount' => $pendingCount,
        'ongoingCount' => $ongoingCount,
        'completedCount' => $completedCount,
    ]);
}


public function bookings(Request $request)
{
    $funeralHomeId = auth()->id();

    $query = \App\Models\Booking::with([
        'client',
        'package',
        'bookingAgent.agentUser'
    ])->where('funeral_home_id', $funeralHomeId);

    // --- Status Filter ---
    $status = $request->input('status', 'all');
    if ($status && $status !== 'all') {
        $query->where('status', $status);
    }

    // --- Search (client, package, agent) ---
    $search = $request->input('search');
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->whereHas('client', fn($q) => $q->where('name', 'like', "%$search%"))
                ->orWhereHas('package', fn($q) => $q->where('name', 'like', "%$search%"))
                ->orWhereHas('bookingAgent.agentUser', fn($q) => $q->where('name', 'like', "%$search%"));
        });
    }

    // --- Sorting ---
    $sort = $request->input('sort', 'created_at');
    $dir = $request->input('dir', 'desc');
    if ($sort === 'client') {
        $query->join('users as clients', 'bookings.client_user_id', '=', 'clients.id')
              ->orderBy('clients.name', $dir)
              ->select('bookings.*');
    } elseif ($sort === 'status') {
        $query->orderBy('status', $dir);
    } else {
        $query->orderBy($sort, $dir);
    }

    // --- Pagination ---
    $bookings = $query->paginate(25)->withQueryString();

    // --- Status Options (Complete List) ---
    $statusOptions = [
        'all'                                          => 'All',
        \App\Models\Booking::STATUS_PENDING            => 'Pending',
        \App\Models\Booking::STATUS_CONFIRMED          => 'Confirmed',
        \App\Models\Booking::STATUS_FOR_PAYMENT_DETAILS=> 'For Payment Details',
        \App\Models\Booking::STATUS_IN_PROGRESS        => 'Client Filling Forms',
        \App\Models\Booking::STATUS_FOR_INITIAL_REVIEW => 'For Initial Review',
        \App\Models\Booking::STATUS_FOR_FINAL_REVIEW   => 'For Final Review',
        \App\Models\Booking::STATUS_SUBMITTED          => 'For Review',
        \App\Models\Booking::STATUS_APPROVED           => 'Approved',
        \App\Models\Booking::STATUS_ONGOING            => 'Ongoing',
        \App\Models\Booking::STATUS_COMPLETED          => 'Completed',
        \App\Models\Booking::STATUS_DECLINED           => 'Declined',
        \App\Models\Booking::STATUS_CANCELLED          => 'Cancelled',
    ];

    // --- Customization Requests (pending only) ---
    $customizationRequests = \App\Models\Booking::with([
        'client',
        'package',
        'customizationRequests' => function ($q) {
            $q->where('status', 'pending');
        }
    ])
    ->where('funeral_home_id', $funeralHomeId)
    ->whereHas('customizationRequests', function($q) {
        $q->where('status', 'pending');
    })
    ->orderByDesc('updated_at')
    ->get();

    return view('funeral.bookings.index', compact(
        'bookings',
        'statusOptions',
        'customizationRequests'
    ));
}


public function show(Booking $booking)
{
    $this->authorizeBooking($booking);

    $booking->load([
        'package.items.category',
        'client',
        'funeralHome',
        'agent',
        'bookingAgent.agentUser',
        // Only eager-load approved cemetery booking (if any), with related plot/cemetery/user
        'cemeteryBooking' => function ($q) {
            $q->where('status', 'approved')
              ->with(['cemetery.user', 'plot']);
        },
        // Add for customized package support if you use it in the funeral view:
        'customizedPackage.items.inventoryItem.category',
        'customizedPackage.items.substituteFor',
    ]);

    // --- Asset Categories WITH PRICE, exactly as in client show ---
    $assetCategories = \DB::table('inventory_categories')
        ->join('package_asset_categories', function ($join) use ($booking) {
            $join->on('package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
                ->where('package_asset_categories.service_package_id', $booking->package->id);
        })
        ->where('inventory_categories.is_asset', 1)
        ->select(
            'inventory_categories.id as id',
            'inventory_categories.name as name',
            'inventory_categories.is_asset',
            'package_asset_categories.price as price'
        )
        ->get();

    $assetCategoryPrices = $assetCategories->pluck('price', 'id')->toArray();

    // Available agents: Must belong to this parlor, be active, and not be assigned to another booking
    $assignedAgentIds = \DB::table('booking_agents')
        ->join('bookings', 'booking_agents.booking_id', '=', 'bookings.id')
        ->where('booking_agents.agent_user_id', '!=', null)
        ->where('bookings.funeral_home_id', $booking->funeral_home_id)
        ->pluck('booking_agents.agent_user_id')
        ->toArray();

    $parlorAgents = \DB::table('users')
        ->join('funeral_home_agent', function ($join) use ($booking) {
            $join->on('users.id', '=', 'funeral_home_agent.agent_user_id')
                ->where('funeral_home_agent.funeral_user_id', $booking->funeral_home_id)
                ->where('funeral_home_agent.status', 'active');
        })
        ->where('users.role', 'agent')
        ->whereNull('users.deleted_at')
        ->whereNotIn('users.id', $assignedAgentIds) // Only show unassigned
        ->select('users.id', 'users.name', 'users.email')
        ->get();

    $invitationStatus = null;
    $bookingAgent = $booking->bookingAgent;
    if ($bookingAgent && $bookingAgent->client_agent_email) {
        $invitation = \DB::table('agent_client_requests')
            ->where('client_id', $booking->client_user_id)
            ->where('booking_id', $booking->id)
            ->orderByDesc('requested_at')
            ->first();
        $invitationStatus = $invitation ? $invitation->status : null;
    }

    // Find assigned plot via booking_details
    $bookingDetail = \App\Models\BookingDetail::where('booking_id', $booking->id)->first();
    $plot = null;
    $plotCemetery = null;
    $cemeteryOwner = null;

    if ($bookingDetail && $bookingDetail->plot_id) {
        $plot = \App\Models\Plot::with('cemetery.user')->find($bookingDetail->plot_id);
        if ($plot) {
            $plotCemetery = $plot->cemetery;
            $cemeteryOwner = $plotCemetery?->user;
        }
    }

    // Pass only the approved cemetery booking to the view (may be null)
    $cemeteryBooking = $booking->cemeteryBooking;

    return view('funeral.bookings.show', compact(
        'booking',
        'assetCategories',
        'assetCategoryPrices',
        'parlorAgents',
        'invitationStatus',
        'bookingAgent',
        'cemeteryBooking',
        'plot',
        'plotCemetery',
        'cemeteryOwner'
    ));
}





public function approve(Request $request, Booking $booking)
{
    $this->authorizeBooking($booking);

    // Determine new status
    if ($booking->status === Booking::STATUS_PENDING) {
        $newStatus = Booking::STATUS_CONFIRMED;
    } elseif (
        $booking->status === Booking::STATUS_SUBMITTED ||
        $booking->status === 'for_review' ||
        $booking->status === Booking::STATUS_PAID ||
        $booking->status === 'paid'
    ) {
        $newStatus = Booking::STATUS_APPROVED;
    } elseif ($booking->status === 'for_initial_review') {
        $newStatus = 'in_progress';
    } else {
        return back()->with('error', 'Booking cannot be approved at this stage.');
    }

    try {
        \DB::transaction(function () use ($booking, $newStatus) {
            // Only get real inventory items (consumables) to deduct
            $items = $booking->customized_package_id && $booking->customizedPackage
                ? $booking->customizedPackage->items
                : $booking->package->items;

            foreach ($items as $item) {
                $invItem = null;
                $category = null;
                $qtyToDeduct = 1;

                if (isset($item->inventoryItem) && $item->inventoryItem) {
                    $invItem = $item->inventoryItem;
                    $qtyToDeduct = $item->quantity ?? 1;
                } elseif ($item instanceof \App\Models\InventoryItem) {
                    $invItem = $item;
                    $qtyToDeduct = $item->pivot->quantity ?? 1;
                }

                if (!$invItem) continue;

                $category = $invItem->category ?? $invItem->load('category')->category;
                if (!$category || $category->is_asset) continue;

                if ($invItem->quantity === null || $invItem->quantity < $qtyToDeduct) {
                    throw new \Exception("Not enough stock for '{$invItem->name}'. Available: {$invItem->quantity}, Required: $qtyToDeduct");
                }

                $invItem->quantity = max(0, $invItem->quantity - $qtyToDeduct);

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
    $packageName = $booking->package->name ?? 'the package';
    $clientName  = $booking->client->name ?? 'the client';
    $parlorName  = $booking->funeralHome->name ?? 'Funeral Parlor';

    // Notify client
    if ($booking->client) {
        if ($newStatus === Booking::STATUS_CONFIRMED) {
            $clientMsg = "Your booking for <b>{$packageName}</b> has been <b>PRE-APPROVED</b>. Please proceed with filling out the required information.";
        } elseif ($newStatus === Booking::STATUS_APPROVED) {
            $clientMsg = "Your booking for <b>{$packageName}</b> has been <b>APPROVED</b>. Wait for the service to start.";
        } elseif ($newStatus === 'in_progress') {
            $clientMsg = "Your booking for <b>{$packageName}</b> has passed the initial review and is now <b>IN PROGRESS</b>.";
        }
        \Log::info('[NOTIFY] Approve: Notifying client', [
            'client_id'   => $booking->client->id ?? null,
            'client_name' => $clientName,
            'message'     => $clientMsg ?? null,
        ]);
        $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $clientMsg, 'client'));
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        if ($newStatus === Booking::STATUS_CONFIRMED) {
            $agentMsg = "A booking for <b>{$packageName}</b> assigned to your client (<b>{$clientName}</b>) at <b>{$parlorName}</b> has been <b>PRE-APPROVED</b>. The client can now fill out the required information.";
        } elseif ($newStatus === Booking::STATUS_APPROVED) {
            $agentMsg = "A booking for <b>{$packageName}</b> assigned to your client (<b>{$clientName}</b>) at <b>{$parlorName}</b> has been <b>APPROVED</b> and is now ready to proceed.";
        } elseif ($newStatus === 'in_progress') {
            $agentMsg = "A booking for <b>{$packageName}</b> assigned to your client (<b>{$clientName}</b>) at <b>{$parlorName}</b> is now <b>IN PROGRESS</b> after initial review.";
        }
        if ($agentUser) {
            \Log::info('[NOTIFY] Approve: Notifying agent', [
                'agent_id'    => $agentUser->id,
                'agent_name'  => $agentUser->name,
                'message'     => $agentMsg ?? null,
            ]);
            $agentUser->notify(new \App\Notifications\BookingStatusChanged($booking, $agentMsg, 'agent'));
        }
    }

    return redirect()->route('funeral.bookings.index')
        ->with('success', 'Booking approved, consumable inventory updated.');
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

    $packageName = $booking->package->name ?? 'the package';
    $clientName  = $booking->client->name ?? 'the client';
    $parlorName  = $booking->funeralHome->name ?? 'Funeral Parlor';

    // Notify client
    if ($booking->client) {
        $clientMsg = "Your booking for <b>{$packageName}</b> at <b>{$parlorName}</b> has been <b>DECLINED</b>.";
        \Log::info('[NOTIFY] Deny: Notifying client', [
            'client_id'   => $booking->client->id ?? null,
            'client_name' => $clientName,
            'message'     => $clientMsg,
        ]);
        $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $clientMsg, 'client'));
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $agentMsg = "A booking for <b>{$packageName}</b> assigned to your client (<b>{$clientName}</b>) at <b>{$parlorName}</b> has been <b>DECLINED</b>.";
        if ($agentUser) {
            \Log::info('[NOTIFY] Deny: Notifying agent', [
                'agent_id'    => $agentUser->id,
                'agent_name'  => $agentUser->name,
                'message'     => $agentMsg,
            ]);
            $agentUser->notify(new \App\Notifications\BookingStatusChanged($booking, $agentMsg, 'agent'));
        }
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


public function manageService(Booking $booking)
{
    // Optionally: authorize
    // $this->authorize('manage', $booking);
        dd($booking);
    return view('funeral.bookings.manage-service', compact('booking'));
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

    // Notify client
    $packageName = $booking->package->name ?? 'the package';
    $message = "Your booking for <b>{$packageName}</b> has been <b>APPROVED</b> and is now ready to start.";
    if ($booking->client) {
        $booking->client->notify(new BookingStatusChanged($booking, $message, 'client'));
    }

    // Notify agent (use bookingAgent, not $booking->agent)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $clientName = $booking->client->name ?? 'the client';
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $agentMsg = "The booking for <b>{$packageName}</b> (client: <b>{$clientName}</b>, parlor: <b>{$parlorName}</b>) was <b>FULLY APPROVED</b> and is ready to start.";
        if ($agentUser) {
            $agentUser->notify(new BookingStatusChanged($booking, $agentMsg, 'agent'));
        }
    }

    return redirect()->route('funeral.bookings.index')->with('success', 'Booking fully approved and ready to start.');
}


// START SERVICE: approved ➔ ongoing
public function startService(Booking $booking)
{
    // Only allow if currently approved
    if ($booking->status !== Booking::STATUS_APPROVED) {
        return back()->with('error', 'Service can only be started from approved bookings.');
    }

    $booking->status = Booking::STATUS_ONGOING;
    $booking->save();

    $bookingId   = $booking->id;
    $packageName = $booking->package->name ?? 'the package';
    $clientName  = $booking->client->name ?? 'the client';
    $parlorName  = $booking->funeralHome->name ?? 'Funeral Parlor';

    // Log "Service started" to booking_service_logs table
    \DB::table('booking_service_logs')->insert([
        'booking_id' => $bookingId,
        'user_id'    => auth()->id(),
        'message'    => "Service started for booking #{$bookingId} ({$packageName}).",
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Notify client
    if ($booking->client) {
        $msg = "The funeral service for <b>{$packageName}</b> (<b>Booking #{$bookingId}</b>) has <b>STARTED</b>.";
        \Log::info('[NOTIFY] Service started: Notifying client', [
            'booking_id' => $bookingId,
            'client_id' => $booking->client->id ?? null,
            'message' => $msg,
        ]);
        $booking->client->notify(new BookingStatusChanged($booking, $msg, 'client'));
    }

    // Notify agent (if any, via booking_agent table)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $msg = "The funeral service for <b>{$packageName}</b> (<b>Booking #{$bookingId}</b>) for client <b>{$clientName}</b> at <b>{$parlorName}</b> has <b>STARTED</b>.";
        if ($agentUser) {
            \Log::info('[NOTIFY] Service started: Notifying agent', [
                'booking_id' => $bookingId,
                'agent_id' => $agentUser->id,
                'message' => $msg,
            ]);
            $agentUser->notify(new BookingStatusChanged($booking, $msg, 'agent'));
        }
    }

    return back()->with('success', 'Service started. Status is now Ongoing.');
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
            'items.inventoryItem.category',
            'items.substituteFor'
        ])
        ->where('booking_id', $bookingId)
        ->findOrFail($customizedPackageId);

    // --- Get all bookable asset categories linked to this package ---
$assetCategories = \DB::table('inventory_categories')
    ->join('package_asset_categories', function ($join) use ($booking) {
        $join->on('package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
            ->where('package_asset_categories.service_package_id', $booking->package_id);
    })
    ->where('inventory_categories.is_asset', 1)
    ->select(
        'inventory_categories.id as id',
        'inventory_categories.name as name',
        'inventory_categories.is_asset',
        'package_asset_categories.price as price'
    )
    ->get();



    return view('funeral.bookings.customization.show', compact('booking', 'customizedPackage', 'assetCategories'));
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

    // Recalculate total: sum of all items + any required unassigned asset categories
    $items = $customized->items()->with('inventoryItem.category')->get();

    // Get assigned asset category IDs
    $assignedAssetCatIds = $items
        ->filter(fn($item) => $item->inventoryItem && ($item->inventoryItem->category->is_asset ?? false))
        ->pluck('inventoryItem.category.id')
        ->unique()
        ->toArray();

    // Get required asset categories for this package
    $assetCategories = \DB::table('package_asset_categories')
        ->join('inventory_categories', 'package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
        ->where('package_asset_categories.service_package_id', $booking->package_id)
        ->where('inventory_categories.is_asset', 1)
        ->select(
            'inventory_categories.id as id',
            'inventory_categories.name as name',
            'inventory_categories.is_asset',
            'package_asset_categories.price as price'
        )
        ->get();

    // Calculate totals
    $itemsTotal = $items->sum(fn($item) => $item->unit_price * $item->quantity);

    $missingAssetTotal = $assetCategories
        ->filter(fn($cat) => !in_array($cat->id, $assignedAssetCatIds))
        ->sum(fn($cat) => $cat->price);

    $subTotal = $itemsTotal + $missingAssetTotal;
    $vat = round($subTotal * 0.12, 2); // Calculate 12% VAT
    $grandTotal = round($subTotal + $vat, 2);

    // Save total with VAT included
    $customized->custom_total_price = $grandTotal;
    $customized->status = 'approved';
    $customized->save();

    $booking->customized_package_id = $customized->id;
    $booking->save();

    // Notify client
    $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
    $clientName = $booking->client->name ?? 'the client';

    $clientMsg = "Your customization request for booking <b>#{$booking->id}</b> at <b>{$parlorName}</b> was <b>APPROVED</b>. The updated package and pricing are now in effect.";
    if ($booking->client) {
        $booking->client->notify(
            new \App\Notifications\CustomizationRequestApproved($booking, $customized, $clientMsg, 'client')
        );
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $agentMsg = "The customization request for booking <b>#{$booking->id}</b> for client <b>{$clientName}</b> at <b>{$parlorName}</b> was <b>APPROVED</b> by the funeral parlor. The updated package and pricing have been applied.";
        if ($agentUser) {
            $agentUser->notify(
                new \App\Notifications\CustomizationRequestApproved($booking, $customized, $agentMsg, 'agent')
            );
        }
    }

    return back()->with('success', 'Customization approved, price recalculated with VAT, and client notified.');
}


// Deny customization request
public function customizationDeny(Request $request, $bookingId, $customizedPackageId)
{
    $booking = \App\Models\Booking::with(['funeralHome', 'client', 'bookingAgent'])->where('funeral_home_id', auth()->id())->findOrFail($bookingId);

    $customized = \App\Models\CustomizedPackage::where('id', $customizedPackageId)
        ->where('booking_id', $booking->id)
        ->where('status', 'pending')
        ->first();

    if (!$customized) {
        return back()->with('error', 'No pending customization request to deny.');
    }

    $customized->status = 'denied';
    $customized->save();

    $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
    $clientName = $booking->client->name ?? 'the client';

    // Notify client
    $clientMsg = "Your request to customize the package for booking <b>#{$booking->id}</b> at <b>{$parlorName}</b> has been <b>DENIED</b> by the funeral parlor. Please contact support if you have questions.";
    if ($booking->client) {
        $booking->client->notify(
            new \App\Notifications\CustomizationRequestDenied($booking, $customized, $clientMsg, 'client')
        );
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $agentMsg = "The customization request for booking <b>#{$booking->id}</b> for client <b>{$clientName}</b> at <b>{$parlorName}</b> was <b>DENIED</b> by the funeral parlor.";
        if ($agentUser) {
            $agentUser->notify(
                new \App\Notifications\CustomizationRequestDenied($booking, $customized, $agentMsg, 'agent')
            );
        }
    }

    return back()->with('success', 'Customization denied. Client and agent notified.');
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

    // --- CALCULATE FINAL AMOUNT FROM booking_details ---
    // Always trust booking_details.amount and booking_details.other_fee
    $amount = floatval($detail->amount ?? 0);
    $otherFee = floatval($detail->other_fee ?? 0);
    $finalAmount = $amount + $otherFee;

    $booking->final_amount = $finalAmount;

    // Update booking status
    $booking->status = Booking::STATUS_IN_PROGRESS;
    $booking->save();

    // Notify the client
    if ($booking->client) {
        $msg = "The funeral parlor has set additional fees for your booking. Please proceed to fill in the deceased's personal details.";
        $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $msg, 'client'));
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $booking->client->name ?? 'the client';
        $msg = "The funeral parlor <b>{$parlorName}</b> has set other fees for booking <b>#{$booking->id}</b> assigned to your client <b>{$clientName}</b>. The booking is now in progress.";
        if ($agentUser) {
            $agentUser->notify(new \App\Notifications\BookingStatusChanged($booking, $msg, 'agent'));
        }
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
        $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $msg, 'client'));
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $booking->client->name ?? 'the client';
        $msg = "The funeral parlor <b>{$parlorName}</b> has updated payment remarks for booking <b>#{$booking->id}</b> assigned to your client <b>{$clientName}</b>.";
        if ($agentUser) {
            $agentUser->notify(new \App\Notifications\BookingStatusChanged($booking, $msg, 'agent'));
        }
    }

    return back()->with('success', 'Payment remarks updated.');
}



public function updateInfo(Request $request, $bookingId)
{
    $booking = Booking::with(['detail', 'package.items', 'customizedPackage', 'client', 'funeralHome', 'bookingAgent'])->findOrFail($bookingId);

    // Only allow users from this funeral home (or with correct role) to edit
    if (
        auth()->user()->role !== 'funeral' ||
        auth()->user()->id != $booking->funeral_home_id
    ) {
        abort(403, 'Unauthorized access');
    }

    $detail = $booking->detail ?: new BookingDetail(['booking_id' => $booking->id]);
    $hasExistingImage = $detail && $detail->deceased_image;

    // Validation - add image logic
    $validated = $request->validate([
        // A. Deceased Personal Details
        'deceased_first_name'        => 'required|string|max:100',
        'deceased_middle_name'       => 'nullable|string|max:100',
        'deceased_last_name'         => 'required|string|max:100',
        'deceased_nickname'          => 'nullable|string|max:100',
        'deceased_residence'         => 'nullable|string|max:255',
        'deceased_sex'               => 'required|in:M,F',
        'deceased_civil_status'      => 'required|string|max:30',
        'deceased_birthday'          => 'nullable|date',
        'deceased_age'               => 'nullable|integer',
        'deceased_date_of_death'     => 'nullable|date',
        'deceased_religion'          => 'nullable|string|max:50',
        'deceased_occupation'        => 'nullable|string|max:100',
        'deceased_citizenship'       => 'nullable|string|max:50',
        'deceased_time_of_death'     => 'nullable|string|max:30',
        'deceased_cause_of_death'    => 'nullable|string|max:255',
        'deceased_place_of_death'    => 'nullable|string|max:255',
        'deceased_father_first_name' => 'nullable|string|max:100',
        'deceased_father_middle_name'=> 'nullable|string|max:100',
        'deceased_father_last_name'  => 'nullable|string|max:100',
        'deceased_mother_first_name' => 'nullable|string|max:100',
        'deceased_mother_middle_name'=> 'nullable|string|max:100',
        'deceased_mother_last_name'  => 'nullable|string|max:100',
        'corpse_disposal'            => 'nullable|string|max:100',
        'interment_cremation_date'   => 'nullable|date',
        'interment_cremation_time'   => 'nullable|string|max:30',
        'cemetery_or_crematory'      => 'nullable|string|max:255',

        // IMAGE upload
        'deceased_image' => [
            $hasExistingImage ? 'nullable' : 'required',
            'image',
            'max:20480'
        ],
        'remove_deceased_image' => 'nullable|in:0,1',

        // B. Documents
        'death_cert_registration_no'     => 'nullable|string|max:100',
        'death_cert_released_to'         => 'nullable|string|max:100',
        'death_cert_released_date'       => 'nullable|date',
        'death_cert_released_signature'  => 'nullable|string',
        'funeral_contract_no'            => 'nullable|string|max:100',
        'funeral_contract_released_to'   => 'nullable|string|max:100',
        'funeral_contract_released_date' => 'nullable|date',
        'funeral_contract_released_signature'=> 'nullable|string',
        'official_receipt_no'            => 'nullable|string|max:100',
        'official_receipt_released_to'   => 'nullable|string|max:100',
        'official_receipt_released_date' => 'nullable|date',
        'official_receipt_released_signature'=> 'nullable|string',

        // C. Informant Details
        'informant_name'             => 'nullable|string|max:100',
        'informant_age'              => 'nullable|integer',
        'informant_civil_status'     => 'nullable|string|max:30',
        'informant_relationship'     => 'nullable|string|max:50',
        'informant_contact_no'       => 'nullable|string|max:30',
        'informant_address'          => 'nullable|string|max:255',

        // D. Service, Amount, Fees
        'amount'     => 'nullable|string|max:100',
        'other_fee'  => 'nullable|string|max:100',
        'deposit'    => 'nullable|string|max:100',
        'cswd'       => 'nullable|string|max:50',
        'dswd'       => 'nullable|string|max:50',
        'remarks'    => 'nullable|string|max:255',

        // E. Certification
        'certifier_name'          => 'nullable|string|max:100',
        'certifier_relationship'  => 'nullable|string|max:50',
        'certifier_residence'     => 'nullable|string|max:255',
        'certifier_amount'        => 'nullable|string|max:255',
        'certifier_signature'     => 'nullable|string|max:255',
        'certifier_signature_image'=> 'nullable|string',
    ]);

    // Autofill service and amount (always enforced)
    $serviceName = $booking->package->name ?? '';
    if ($booking->customizedPackage && $booking->customizedPackage->status === 'approved') {
        $totalAmount = $booking->customizedPackage->custom_total_price;
    } elseif ($booking->package) {
        $totalAmount = $booking->package->items->sum(function ($item) {
            return ($item->pivot->quantity ?? 1) * ($item->selling_price ?? $item->price ?? 0);
        });
    } else {
        $totalAmount = 0;
    }

    // Save or update BookingDetail
    $detail->fill($validated);
    $detail->service = $serviceName;
    $detail->amount = $validated['amount'] ?? 0;
    $detail->booking_id = $booking->id;

    // Signature images
    $detail->death_cert_released_signature         = $validated['death_cert_released_signature'] ?? null;
    $detail->funeral_contract_released_signature   = $validated['funeral_contract_released_signature'] ?? null;
    $detail->official_receipt_released_signature   = $validated['official_receipt_released_signature'] ?? null;
    $detail->certifier_signature_image             = $validated['certifier_signature_image'] ?? null;

    // -------- Handle Deceased Image Upload --------
    if ($request->input('remove_deceased_image') === '1') {
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $detail->deceased_image = null;
    } elseif ($request->hasFile('deceased_image')) {
        // Remove old image if any
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $path = $request->file('deceased_image')->store('deceased_images', 'public');
        $detail->deceased_image = $path;
    }
    // If not removing and no upload, old image stays

    $detail->save();

    // Notify client
    if ($booking->client) {
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $message = "The funeral parlor ({$parlorName}) has updated the information for your booking #{$booking->id}.";
        $booking->client->notify(
            new \App\Notifications\BookingStatusChanged($booking, $message)
        );
    }

    // Notify agent (if any)
    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $booking->client->name ?? 'the client';
        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
        $bookingUrl = route('agent.bookings.show', $booking->id);

        $agentMessage = "The funeral parlor <b>{$parlorName}</b> has updated the information for booking <b>#{$booking->id}</b> assigned to your client <b>{$clientName}</b>.";

        if ($agentUser) {
            $agentUser->notify(
                new \App\Notifications\BookingStatusChanged($booking, $agentMessage, 'agent')
            );
        }
    }

    // No status change here; only the client triggers the "for_review" status change.

    return redirect()
        ->route('funeral.bookings.show', $booking->id)
        ->with('success', 'Personal & service details have been updated for this booking.');
}


// PHASE 3 FORM: Info of the Dead (Funeral Parlor Side)
public function editInfo($bookingId)
{
    $booking = \App\Models\Booking::with([
        'detail',
        'package',            // service_package relation
        'customizedPackage',  // customized_packages relation
        'client',             // for display/use in blade
    ])->findOrFail($bookingId);

    // Authorization: only funeral staff from the correct parlor can access
    if (
        auth()->user()->role !== 'funeral' ||
        auth()->user()->id != $booking->funeral_home_id
    ) {
        abort(403, 'Unauthorized access');
    }


    // Set editability: Funeral parlor can help edit IF not completed/finalized
    // (Allow edit for 'in_progress', 'for_initial_review', 'for_review')


    // Amount logic (always double check relations)
    $totalAmount = $booking->detail->amount ?? 0;


    // Optionally get client name for header display
    $clientName = $booking->client->name ?? null;

    return view('funeral.bookings.editInfo', [
        'booking'     => $booking,
        'detail'      => $booking->detail,
        'packageName' => $booking->package->name ?? '',
        'totalAmount' => $totalAmount,
        'clientName'  => $clientName,
    ]);
}


public function payWithLink(Request $request, $bookingId)
{
    try {
        \Log::info('[payWithLink] Start for bookingId: ' . $bookingId);

        $booking = Booking::findOrFail($bookingId);
        \Log::info('[payWithLink] Booking loaded', ['booking_id' => $booking->id]);

        $currentUser = auth()->user();
        \Log::info('[payWithLink] Current User:', [
            'user_id' => $currentUser->id,
            'user_email' => $currentUser->email,
            'role' => $currentUser->role
        ]);

        // Allow only client or funeral parlor user
        if (!in_array($currentUser->role, ['client', 'funeral'])) {
            \Log::warning('[payWithLink] Unauthorized role attempted to create payment link', [
                'role' => $currentUser->role,
                'user_id' => $currentUser->id
            ]);
            abort(403);
        }

        $convenienceFee = 25;
        $amount = $booking->final_amount ?? ($booking->detail->amount ?? 0);
        \Log::info('[payWithLink] Amounts:', [
            'amount' => $amount,
            'final_amount' => $booking->final_amount,
            'detail_amount' => $booking->detail->amount ?? null,
        ]);
        $totalWithFee = $amount + $convenienceFee;

        $secretKey = config('services.paymongo.secret');
        \Log::info('[payWithLink] PayMongo secret key (masked):', ['secret' => substr($secretKey, 0, 5) . '****']);

        if (empty($secretKey)) {
            \Log::error('[payWithLink] Missing PayMongo secret key.');
            abort(500, 'Missing PayMongo secret key.');
        }

        $description = "Payment for Package: " . ($booking->package->name ?? 'Package');
        $remarks = "Booking ID: {$booking->id} | Initiator: " . ($currentUser->name ?? '');

        \Log::info('[payWithLink] Payload data', [
            'description' => $description,
            'remarks' => $remarks,
            'total_with_fee' => $totalWithFee,
        ]);

        $payload = [
            "data" => [
                "attributes" => [
                    "amount" => intval(round($totalWithFee * 100)),
                    "currency" => "PHP",
                    "description" => $description,
                    "remarks" => $remarks,
                ]
            ]
        ];

        \Log::info('[payWithLink] Payload JSON', $payload);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            "Authorization" => "Basic " . base64_encode($secretKey . ":"),
            "Content-Type" => "application/json"
        ])->post("https://api.paymongo.com/v1/links", $payload);

        \Log::info('[payWithLink] PayMongo API response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $result = $response->json();

        // Log for debugging
        \Log::info('[payWithLink] PayMongo Link Response:', $result);

        if (isset($result['data']['attributes']['checkout_url'])) {
            $referenceId = $result['data']['id'];
            $referenceNumber = $result['data']['attributes']['reference_number'] ?? null;

            \Log::info('[payWithLink] PayMongo reference ID to save:', ['reference_id' => $referenceId]);
            \Log::info('[payWithLink] PayMongo reference number to save:', ['reference_number' => $referenceNumber]);

            // Find any existing payment for this booking
            $payment = $booking->payments()->first();

            if ($payment) {
                \Log::info('[payWithLink] Existing payment found. Updating.', ['payment_id' => $payment->id]);
                $payment->update([
                    'amount' => $amount,
                    'convenience_fee' => $convenienceFee,
                    'status' => 'pending',
                    'notes' => 'Payment Link updated. Awaiting payment. Ref#: ' . $referenceNumber,
                    'reference_id' => $referenceId,
                    'reference_number' => $referenceNumber,
                    'raw_response' => json_encode($result),
                ]);
                \Log::info('[payWithLink] Existing payment updated', [
                    'payment_id' => $payment->id,
                    'fields' => [
                        'amount' => $amount,
                        'convenience_fee' => $convenienceFee,
                        'status' => 'pending',
                        'notes' => 'Payment Link updated. Awaiting payment. Ref#: ' . $referenceNumber,
                        'reference_id' => $referenceId,
                        'reference_number' => $referenceNumber,
                        'raw_response' => '[json]'
                    ]
                ]);
            } else {
                \Log::info('[payWithLink] No existing payment found. Creating new payment row...');
                $payment = $booking->payments()->create([
                    'amount' => $amount,
                    'convenience_fee' => $convenienceFee,
                    'method' => 'paymongo_link',
                    'status' => 'pending',
                    'notes'  => 'Payment Link created. Awaiting payment. Ref#: ' . $referenceNumber,
                    'reference_id' => $referenceId,
                    'reference_number' => $referenceNumber,
                    'raw_response' => json_encode($result),
                ]);
                \Log::info('[payWithLink] New payment created', [
                    'payment_id' => $payment->id,
                    'fields' => [
                        'amount' => $amount,
                        'convenience_fee' => $convenienceFee,
                        'method' => 'paymongo_link',
                        'status' => 'pending',
                        'notes'  => 'Payment Link created. Awaiting payment. Ref#: ' . $referenceNumber,
                        'reference_id' => $referenceId,
                        'reference_number' => $referenceNumber,
                        'raw_response' => '[json]'
                    ]
                ]);
            }

            \Log::info('[payWithLink] Redirecting to checkout_url', [
                'url' => $result['data']['attributes']['checkout_url']
            ]);
            return redirect($result['data']['attributes']['checkout_url']);
        } else {
            \Log::error('[payWithLink] No checkout_url found in PayMongo response!', ['response' => $result]);
            return back()->withErrors(['payment' => 'Error creating payment link: ' . json_encode($result)]);
        }
    } catch (\Throwable $e) {
        \Log::error('[payWithLink] Exception thrown', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        abort(500, 'A server error occurred. Please contact support.');
    }
}



    public function showPayment($bookingId)
    {
        $booking = \App\Models\Booking::findOrFail($bookingId);
        return view('funeral.bookings.payment', compact('booking'));
    }





/*
public function payWithPayMongo(Request $request, $bookingId)
{
    $booking = Booking::with(['payments', 'bookingAgent'])->findOrFail($bookingId);

    // Allow: client, assigned agent, or parlor
    $user = auth()->user();
    $isClient = $booking->client_user_id === $user->id;
    $isAgent = $booking->bookingAgent && $booking->bookingAgent->agent_user_id === $user->id;
    $isParlor = $booking->funeral_home_id === $user->id;

    if (!($isClient || $isAgent || $isParlor)) {
        abort(403, 'Unauthorized payment attempt.');
    }

    // Get payment type (default to card)
    $paymentType = $request->input('payment_type', 'card');
    $amount = $booking->final_amount ?? $booking->detail->amount ?? 0;
    $amountInCents = intval(round($amount * 100));

    // For CARD payment
    if ($paymentType === 'card') {
        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->post('https://api.paymongo.com/v1/payment_intents', [
                    'data' => [
                        'attributes' => [
                            'amount' => $amountInCents,
                            'payment_method_allowed' => ['card'],
                            'currency' => 'PHP',
                            'description' => 'Funeral Booking Payment #' . $booking->id,
                        ]
                    ]
                ]);
            $responseData = $response->json();
            if (!isset($responseData['data'])) {
                \Log::error('PayMongo CARD error', $responseData);
                $errorMessage = $responseData['errors'][0]['detail'] ?? 'Unknown error creating PaymentIntent.';
                return back()->withErrors(['payment' => 'Card payment failed: ' . $errorMessage]);
            }

            $paymentIntent = $responseData['data']['id'];
            $clientKey = $responseData['data']['attributes']['client_key'];

            $attach = \Illuminate\Support\Facades\Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->post("https://api.paymongo.com/v1/payment_intents/{$paymentIntent}/attach", [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $validated['payment_method_id'],
                            'client_key' => $clientKey,
                        ]
                    ]
                ]);
            $attachData = $attach->json();

            if (!isset($attachData['data'])) {
                \Log::error('PayMongo CARD attach error', $attachData);
                $errorMessage = $attachData['errors'][0]['detail'] ?? 'Unknown error attaching payment method.';
                return back()->withErrors(['payment' => 'Card payment failed: ' . $errorMessage]);
            }

            $attachedIntent = $attachData['data']['attributes'];

            if ($attachedIntent['status'] == 'succeeded') {
                $booking->payments()->create([
                    'amount' => $amount,
                    'method' => 'paymongo_card',
                    'status' => 'paid',
                    'notes'  => 'Paid via card (PayMongo JS)',
                ]);
                $booking->status = 'paid';
                $booking->save();
                return redirect()->route('funeral.dashboard')
                    ->with('success', 'Payment successful! Thank you for your payment.');
            } else {
                return back()->withErrors(['payment' => 'Card declined or payment failed.']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['payment' => 'Payment failed: ' . $e->getMessage()]);
        }
    }

    // For GCASH payment (QR/E-Wallet)
    if ($paymentType === 'gcash') {
        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->post('https://api.paymongo.com/v1/sources', [
                    'data' => [
                        'attributes' => [
                            'amount' => $amountInCents,
                            'redirect' => [
                                'success' => route('funeral.bookings.paymongo.success', $booking->id),
                                'failed' => route('funeral.bookings.paymongo.failed', $booking->id),
                            ],
                            'type' => 'gcash',
                            'currency' => 'PHP',
                        ]
                    ]
                ]);
            $responseData = $response->json();

            if (!isset($responseData['data'])) {
                \Log::error('PayMongo GCASH error', $responseData);
                $errorMessage = $responseData['errors'][0]['detail'] ?? 'Unknown error creating GCash source.';
                return back()->withErrors(['payment' => 'GCash payment failed: ' . $errorMessage]);
            }

            $source = $responseData['data'];
            $redirectUrl = $source['attributes']['redirect']['checkout_url'] ?? null;

            $booking->payments()->create([
                'amount' => $amount,
                'method' => 'paymongo_gcash',
                'status' => 'pending',
                'notes' => 'GCash payment initiated. Awaiting confirmation.',
                'reference_id' => $source['id'],
            ]);
            $booking->status = 'pending_payment';
            $booking->save();

            if ($redirectUrl) {
                return redirect($redirectUrl);
            } else {
                return back()->withErrors(['payment' => 'Failed to create GCash source.']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['payment' => 'GCash payment failed: ' . $e->getMessage()]);
        }
    }

    return back()->withErrors(['payment' => 'Invalid payment method.']);
}

public function showPayment($bookingId)
{
    $booking = Booking::with(['detail', 'customizedPackage', 'bookingAgent'])->findOrFail($bookingId);

    // Allow: client, assigned agent, or parlor
    $user = auth()->user();
    $isClient = $booking->client_user_id === $user->id;
    $isAgent = $booking->bookingAgent && $booking->bookingAgent->agent_user_id === $user->id;
    $isParlor = $booking->funeral_home_id === $user->id;

    if (!($isClient || $isAgent || $isParlor)) {
        abort(403, 'Unauthorized access.');
    }

    $amount = $booking->final_amount ?? 0;
    $amountInCents = intval(round($amount * 100));

    return view('funeral.bookings.payment', [
        'booking' => $booking,
        'amount' => $amount,
        'amountInCents' => $amountInCents,
    ]);
}


public function paymongoSuccess($bookingId)
{
    // Update payment as paid (ideally check via webhook, but update here for demo)
    $booking = \App\Models\Booking::findOrFail($bookingId);
    $payment = $booking->payments()->where('method', 'paymongo_gcash')->latest()->first();
    if ($payment && $payment->status !== 'paid') {
        $payment->status = 'paid';
        $payment->save();
        $booking->status = 'paid';
        $booking->save();

        $packageName = $booking->package->name ?? 'the package';
        $clientName  = $booking->client->name ?? 'the client';
        $parlorName  = $booking->funeralHome->name ?? 'Funeral Parlor';

        // ==== Notify Assigned Agent (if any) ====
        if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
            $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
            if ($agentUser) {
                $agentMsg = "Your client <b>{$clientName}</b> has <b>PAID</b> for booking <b>#{$booking->id}</b> ({$packageName}) at <b>{$parlorName}</b>. The funeral parlor will now proceed with the next steps.";
                $agentUser->notify(new \App\Notifications\BookingStatusChanged($booking, $agentMsg, 'agent'));
            }
        }

        // ==== Notify Client ====
        if ($booking->client) {
            $clientMsg = "You have <b>SUCCESSFULLY PAID</b> for your booking <b>#{$booking->id}</b> ({$packageName}) at <b>{$parlorName}</b>. The funeral parlor will proceed with the next steps.";
            $booking->client->notify(new \App\Notifications\BookingStatusChanged($booking, $clientMsg, 'client'));
        }
    }

    return redirect()->route('funeral.bookings.index')->with('success', 'Payment successful!');
}



public function paymongoFailed($bookingId)
{
    // Optionally, you can mark the latest GCash payment as failed/cancelled for audit.
    $booking = \App\Models\Booking::findOrFail($bookingId);

    // Find the most recent GCash payment record for this booking
    $payment = $booking->payments()
        ->where('method', 'paymongo_gcash')
        ->orderByDesc('created_at')
        ->first();

    if ($payment && $payment->status !== 'paid') {
        $payment->status = 'failed';
        $payment->notes = 'User was redirected to failed/cancelled from PayMongo GCash.';
        $payment->save();
    }

    // Optionally revert booking status if needed
    if ($booking->status == 'pending_payment') {
        $booking->status = 'in_progress'; // or whatever previous status
        $booking->save();
    }

    return redirect()
        ->route('funeral.bookings.payment', $bookingId)
        ->withErrors(['payment' => 'GCash payment was cancelled or failed. Please try again.']);
}
*/


}
