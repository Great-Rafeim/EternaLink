<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingAgent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ServicePackage;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\CustomizedPackage;
use App\Models\CustomizedPackageItem;
use App\Notifications\CustomizationRequestSubmitted;

class AgentDashboardController extends Controller
{
public function index()
{
    $agentId = Auth::id();

    // Get all booking IDs assigned to this agent via BookingAgent
    $agentBookingIds = BookingAgent::where('agent_user_id', $agentId)
        ->pluck('booking_id');

    $bookings = Booking::whereIn('id', $agentBookingIds)
        ->with(['client', 'funeralHome', 'package'])
        ->get();

    return view('agent.dashboard', ['bookings' => $bookings]);
}

public function show(Booking $booking)
{
    $agentId = auth()->id();

    // Agent must be the direct agent or the related booking agent
    if (
        $booking->agent_user_id !== $agentId &&
        (!$booking->bookingAgent || $booking->bookingAgent->agent_user_id !== $agentId)
    ) {
        abort(403, 'Unauthorized');
    }

    // Eager load relationships (other relations unchanged)
    $booking->load([
        'package.items.category',
        'client',
        'funeralHome',
        'agent',
        'bookingAgent.agentUser',
        // Not using cemeteryBooking for plot/cemetery display!
    ]);

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

    $assetCategories = \DB::table('package_asset_categories')
        ->join('inventory_categories', 'package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
        ->where('package_asset_categories.service_package_id', $booking->package->id)
        ->where('inventory_categories.is_asset', 1)
        ->select('inventory_categories.id', 'inventory_categories.name')
        ->get();

    $bookingAgent = $booking->bookingAgent;

    $serviceLogs = \App\Models\BookingServiceLog::with('user')
        ->where('booking_id', $booking->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Find assigned plot via booking_details (same logic as in funeral controller)
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

    // Now you can use $plot, $plotCemetery, $cemeteryOwner in your view!
    return view('agent.bookings.show', compact(
        'booking',
        'packageItems',
        'assetCategories',
        'bookingAgent',
        'serviceLogs',
        'plot',
        'plotCemetery',
        'cemeteryOwner'
    ));
}


public function editBooking($bookingId)
{
    $booking = Booking::with([
        'funeralHome',
        'package.items.category',
        'customizedPackage',
        'agentAssignment'
    ])->findOrFail($bookingId);

    $agentId = auth()->id();
    $isAssignedAgent = $booking->agent_user_id === $agentId
        || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);

    if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
        abort(403, 'Unauthorized');
    }

    $customized = $booking->customizedPackage
        ?? CustomizedPackage::firstOrCreate(
            ['booking_id' => $booking->id],
            ['original_package_id' => $booking->package_id]
        );

    $customItems = $customized->items()->get()->map(function ($item) {
        return [
            'item_id'        => $item->inventory_item_id,
            'quantity'       => $item->quantity,
            'substitute_for' => $item->substitute_for,
        ];
    });

    $allItems = InventoryItem::where('funeral_home_id', $booking->funeral_home_id)
        ->where('status', 'available')
        ->where('quantity', '>', 0)
        ->get()
        ->groupBy('inventory_category_id');

    // Fetch all asset categories for this package (for display and for total calculation)
    $assetCategories = \DB::table('package_asset_categories')
        ->join('inventory_categories', 'package_asset_categories.inventory_category_id', '=', 'inventory_categories.id')
        ->where('package_asset_categories.service_package_id', $booking->package_id)
        ->select(
            'inventory_categories.id',
            'inventory_categories.name',
            'inventory_categories.is_asset',
            'package_asset_categories.price as category_price'
        )
        ->where('inventory_categories.is_asset', 1)
        ->get();

    logger('ASSET CATEGORIES:', $assetCategories->toArray());

    $packageName = $booking->package->name ?? '';

    // 1. Get item total (sum of items, as before)
    $itemTotal = $booking->package
        ? $booking->package->items->sum(function($item) {
            $qty = $item->pivot->quantity ?? 1;
            $price = $item->selling_price ?? $item->price ?? 0;
            logger("Item #{$item->id} qty: $qty, price: $price, subtotal: " . ($qty * $price));
            return $qty * $price;
        })
        : 0;

    logger('ITEM TOTAL:', [$itemTotal]);

    // 2. Identify asset categories that are NOT covered by any item
    $assetCategoryIdsInItems = $booking->package
        ? $booking->package->items->pluck('inventory_category_id')->unique()->toArray()
        : [];

    logger('ASSET CATEGORY IDS IN ITEMS:', $assetCategoryIdsInItems);

    // 3. Sum prices of "extra" asset categories (from package_asset_categories) not covered by any item
    $extraAssetCategories = \DB::table('package_asset_categories')
        ->where('service_package_id', $booking->package_id)
        ->whereNotIn('inventory_category_id', $assetCategoryIdsInItems)
        ->get();

    $extraAssetCategoryTotal = $extraAssetCategories->sum('price');

    logger('EXTRA ASSET CATEGORIES:', $extraAssetCategories->toArray());
    logger('EXTRA ASSET CATEGORY TOTAL:', [$extraAssetCategoryTotal]);

    // 4. Decide final total amount: customized price if approved, else sum items + extra asset categories
    $totalAmount = ($booking->customizedPackage && $booking->customizedPackage->status === 'approved')
        ? $booking->customizedPackage->custom_total_price
        : ($itemTotal + $extraAssetCategoryTotal);

    logger('FINAL TOTAL AMOUNT:', [$totalAmount]);

    return view('agent.bookings.editBooking', [
        'booking'         => $booking,
        'customized'      => $customized,
        'customItems'     => $customItems,
        'allItems'        => $allItems,
        'assetCategories' => $assetCategories,
        'agentAssignment' => $booking->agentAssignment,
        'packageName'     => $packageName,
        'totalAmount'     => $totalAmount,
    ]);
}


    // UPDATE BOOKING FORM (AGENT SIDE)
// UPDATE BOOKING FORM (AGENT SIDE)
public function updateBooking(Request $request, $bookingId)
{
    $booking = Booking::with(['detail', 'customizedPackage', 'package.items', 'agentAssignment', 'funeralHome', 'client'])
        ->findOrFail($bookingId);

    $agentId = auth()->id();
    $isAssignedAgent = $booking->agent_user_id === $agentId
        || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
    if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
        abort(403, 'Unauthorized');
    }

    $detail = $booking->detail ?? new \App\Models\BookingDetail(['booking_id' => $booking->id]);

    $validated = $request->validate([
        'wake_start_date'      => 'nullable|date',
        'wake_end_date'        => 'nullable|date|after_or_equal:wake_start_date',
        'interment_cremation_date' => 'nullable|date',
        'cemetery_or_crematory'=> 'nullable|string|max:255',
        'has_plot_reserved'    => 'nullable|boolean',
        'attire'               => 'nullable|string|max:255',
        'need_agent'           => 'nullable|in:yes,no',
        'agent_type'           => 'nullable|in:client,parlor',
        'client_agent_email'   => 'nullable|email',
        'post_services'        => 'nullable|string|max:255',
        'payment_method'       => 'required|in:full,installment',
        'amount'               => 'required|numeric|min:0',
        'installment_duration' => 'nullable|integer|min:2|max:36',
        'death_certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20240',
    ]);

    // Always use the form's amount for detail & booking, just like on the client side!
    $amount = $validated['amount'];

    $detail->wake_start_date           = $validated['wake_start_date'] ?? null;
    $detail->wake_end_date             = $validated['wake_end_date'] ?? null;
    $detail->interment_cremation_date  = $validated['interment_cremation_date'] ?? null;
    $detail->cemetery_or_crematory     = $validated['cemetery_or_crematory'] ?? null;
    $detail->has_plot_reserved         = $validated['has_plot_reserved'] ?? null;
    $detail->attire                    = $validated['attire'] ?? null;
    $detail->post_services             = $validated['post_services'] ?? null;
    $detail->amount                    = $amount;

    if ($request->hasFile('death_certificate_file')) {
        if ($detail->death_certificate_path && \Storage::disk('public')->exists($detail->death_certificate_path)) {
            \Storage::disk('public')->delete($detail->death_certificate_path);
        }
        $file = $request->file('death_certificate_file');
        $path = $file->store('death_certificates', 'public');
        $detail->death_certificate_path = $path;
    }
    $detail->save();

    // ---- Update the final_amount in bookings table (excluding other_fee) ----
    $booking->final_amount = $amount;

    $agentAssignment = [
        'booking_id'         => $booking->id,
        'need_agent'         => $validated['need_agent'] ?? null,
        'agent_type'         => $validated['agent_type'] ?? null,
        'client_agent_email' => $validated['client_agent_email'] ?? null,
    ];

    if ($booking->agentAssignment) {
        $booking->agentAssignment->update($agentAssignment);
    } else {
        BookingAgent::create($agentAssignment);
    }

    $booking->payments()->delete();

    if ($validated['payment_method'] === 'full') {
        Payment::create([
            'booking_id'     => $booking->id,
            'amount'         => $amount,
            'method'         => 'full',
            'installment_no' => null,
            'due_date'       => now()->addDays(7),
            'status'         => 'pending',
            'notes'          => 'Full payment for booking.',
        ]);
    } else {
        $duration = $validated['installment_duration'] ?? 12;
        $installmentAmount = round($amount / $duration, 2);
        $dueDate = now()->addDays(7);

        for ($i = 1; $i <= $duration; $i++) {
            Payment::create([
                'booking_id'     => $booking->id,
                'amount'         => $installmentAmount,
                'method'         => 'installment',
                'installment_no' => $i,
                'due_date'       => $dueDate->copy()->addMonths($i - 1),
                'status'         => 'pending',
                'notes'          => "Installment {$i} of {$duration}",
            ]);
        }
    }

    $booking->status = 'for_initial_review';
    $booking->save();

    if ($booking->funeralHome && $booking->client) {
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $booking->client->name ?? 'Client';
        $bookingNumber = $booking->id;

        $msgParlor = "Booking #{$bookingNumber} has been updated and submitted for initial review by assigned agent for {$clientName}. "
            . "Please review and set other fees if applicable. [Funeral Parlor: {$parlorName}]";

        $booking->funeralHome->notify(
            new \App\Notifications\BookingStatusChanged($booking, $msgParlor)
        );

        $msgClient = "Your assigned agent has updated and submitted your booking #{$bookingNumber} for review. Funeral Parlor: {$parlorName}.";
        $booking->client->notify(
            new \App\Notifications\BookingStatusChanged($booking, $msgClient)
        );
    }

    return redirect()->route('agent.bookings.show', $booking->id)
        ->with('success', 'Booking details saved and sent for review.');
}


    // PHASE 3 FORM: Info of the Dead
    public function editInfo($bookingId)
    {
        $booking = \App\Models\Booking::with([
            'detail',
            'package',
            'customizedPackage',
            'client',
            'funeralHome',
        ])->findOrFail($bookingId);

        $agentId = auth()->id();
        $isAssignedAgent = $booking->agent_user_id === $agentId
            || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
        if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
            abort(403, 'Unauthorized access');
        }

        if ($booking->customized_package_id && $booking->customizedPackage) {
            $totalAmount = $booking->customizedPackage->custom_total_price ?? 0;
        } else {
            $totalAmount = $booking->package->total_price ?? 0;
        }
        $clientName = $booking->client->name ?? null;

        $agentName = auth()->user()->name;
        $client = $booking->client;
        $funeralHome = $booking->funeralHome;

        if ($client) {
            $message = "Your assigned agent ({$agentName}) is updating information for your booking #{$booking->id}.";
            $client->notify(
                new \App\Notifications\BookingStatusChanged($booking, $message)
            );
        }

        if ($funeralHome) {
            $message = "Agent ({$agentName}) is editing the details for Booking #{$booking->id} (Client: {$clientName}).";
            $funeralHome->notify(
                new \App\Notifications\BookingStatusChanged($booking, $message)
            );
        }

        return view('agent.bookings.editDeceased', [
            'booking'     => $booking,
            'detail'      => $booking->detail,
            'packageName' => $booking->package->name ?? '',
            'totalAmount' => $totalAmount,
            'clientName'  => $clientName,
        ]);
    }

public function updateInfo(Request $request, $bookingId)
{
    $booking = \App\Models\Booking::with(['detail', 'package.items', 'customizedPackage', 'client', 'funeralHome'])->findOrFail($bookingId);

    $agentId = auth()->id();
    $isAssignedAgent = $booking->agent_user_id === $agentId
        || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
    if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
        abort(403, 'Unauthorized access');
    }

    $detail = $booking->detail ?: new \App\Models\BookingDetail(['booking_id' => $booking->id]);
    $hasExistingImage = $detail && $detail->deceased_image;

    // Validation: image is required if there is not yet an image; else nullable.
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

        // Deceased image
        'deceased_image'             => [
            $hasExistingImage ? 'nullable' : 'required',
            'image',
            'max:20480'
        ],

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
        'remove_deceased_image'   => 'nullable|in:0,1'
    ]);

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

    $detail->fill($validated);
    $detail->service = $serviceName;
    $detail->amount = $validated['amount'] ?? 0;
    $detail->booking_id = $booking->id;
    $detail->death_cert_released_signature         = $validated['death_cert_released_signature'] ?? null;
    $detail->funeral_contract_released_signature   = $validated['funeral_contract_released_signature'] ?? null;
    $detail->official_receipt_released_signature   = $validated['official_receipt_released_signature'] ?? null;
    $detail->certifier_signature_image             = $validated['certifier_signature_image'] ?? null;

    // --- Handle Deceased Image Upload ---
    if ($request->input('remove_deceased_image') === '1') {
        // Remove the image if requested
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $detail->deceased_image = null;
    } elseif ($request->hasFile('deceased_image')) {
        // Delete old image first (if exists)
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $path = $request->file('deceased_image')->store('deceased_images', 'public');
        $detail->deceased_image = $path;
    }
    // If not removing and no new upload, keep old image.

    $detail->save();

    // Notify client
    if ($booking->client) {
        $agentName = auth()->user()->name;
        $message = "Your assigned agent ({$agentName}) has updated the information for your booking #{$booking->id}.";
        $booking->client->notify(
            new \App\Notifications\BookingStatusChanged($booking, $message)
        );
    }

    // Notify funeral home
    if ($booking->funeralHome) {
        $agentName = auth()->user()->name;
        $clientName = $booking->client->name ?? 'the client';
        $message = "Agent ({$agentName}) updated the details for Booking #{$booking->id} on behalf of {$clientName}.";
        $booking->funeralHome->notify(
            new \App\Notifications\BookingStatusChanged($booking, $message)
        );
    }

    return redirect()
        ->route('agent.bookings.show', $booking->id)
        ->with('success', 'Personal & service details have been updated for this booking.');
}


    public function editCustomization($bookingId)
    {
        $booking = Booking::with(['funeralHome', 'package'])->findOrFail($bookingId);

        $agentId = auth()->id();
        $isAssignedAgent = $booking->agent_user_id === $agentId
            || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
        if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
            abort(403, 'Unauthorized access');
        }

        $customized = CustomizedPackage::firstOrCreate(
            ['booking_id' => $booking->id],
            ['original_package_id' => $booking->package_id]
        );

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

        return view('agent.bookings.package-customization.edit', compact('booking', 'customized', 'items', 'inventory'));
    }

    public function updateCustomization(Request $request, $bookingId)
    {
        $booking = Booking::with('funeralHome')->findOrFail($bookingId);

        $agentId = auth()->id();
        $isAssignedAgent = $booking->agent_user_id === $agentId
            || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
        if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
            abort(403, 'Unauthorized access');
        }

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

public function sendCustomizationRequest(Request $request, $bookingId)
{
    $booking = Booking::with(['funeralHome', 'package', 'client'])->findOrFail($bookingId);

    $agentId = auth()->id();
    $isAssignedAgent = ($booking->agent_user_id === $agentId)
        || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
    if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
        abort(403, 'Unauthorized access');
    }

    $customized = CustomizedPackage::firstOrCreate(
        ['booking_id' => $booking->id],
        ['original_package_id' => $booking->package_id]
    );

    $input = $request->input('custom_items', []);
    \DB::beginTransaction();

    try {
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

        // ---- CUSTOMIZED NOTIFICATION MESSAGES ----
        $packageName = $booking->package->name ?? 'the package';
        $agentName   = auth()->user()->name ?? 'the agent';
        $clientName  = $booking->client->name ?? 'the client';
        $parlorName  = $booking->funeralHome->name ?? 'Funeral Parlor';

        // For Funeral Parlor (role: funeral)
        $parlorMsg = "A customization request for booking <b>#{$booking->id}</b> (package: <b>{$packageName}</b>) was submitted by agent <b>{$agentName}</b> for client <b>{$clientName}</b>. Please review and approve or deny.";
        $booking->funeralHome->notify(
            new \App\Notifications\CustomizationRequestSubmitted($customized, $parlorMsg, 'funeral')
        );

        // For Client (role: client)
        if ($booking->client) {
            $clientMsg = "Your customization request for booking <b>#{$booking->id}</b> (<b>{$packageName}</b>) has been submitted by your agent (<b>{$agentName}</b>) and is awaiting review by <b>{$parlorName}</b>.";
            $booking->client->notify(
                new \App\Notifications\CustomizationRequestSubmitted($customized, $clientMsg, 'client')
            );
        }

        \DB::commit();
        return back()->with('success', 'Customization request sent. Awaiting parlor approval.');
    } catch (\Exception $e) {
        \DB::rollBack();
        return back()->withErrors(['customization_error' => $e->getMessage()]);
    }
}


}
