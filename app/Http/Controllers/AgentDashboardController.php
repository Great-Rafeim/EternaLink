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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        // If you support customized packages for agents:
        'customizedPackage.items.inventoryItem.category',
        'customizedPackage.items.substituteFor',
    ]);

    // --- Asset Categories WITH PRICE, matching client & funeral controller ---
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

    // Pass all required variables, NO packageItems array!
    return view('agent.bookings.show', compact(
        'booking',
        'assetCategories',
        'assetCategoryPrices',
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
        'customizedPackage.items.inventoryItem.category',
        'customizedPackage.items.substituteFor',
        'agentAssignment',
        'detail',
    ])->findOrFail($bookingId);

    $agentId = auth()->id();
    $isAssignedAgent = $booking->agent_user_id === $agentId
        || ($booking->agentAssignment && $booking->agentAssignment->agent_user_id === $agentId);

    if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
        abort(403, 'Unauthorized');
    }

    // Customized package handling (create if missing)
    $customized = $booking->customizedPackage
        ?? CustomizedPackage::firstOrCreate(
            ['booking_id' => $booking->id],
            ['original_package_id' => $booking->package_id]
        );

    // Prepare customItems array if needed by the view
    $customItems = $customized->items()->get()->map(function ($item) {
        return [
            'item_id'        => $item->inventory_item_id,
            'quantity'       => $item->quantity,
            'substitute_for' => $item->substitute_for,
        ];
    });

    // All available inventory items for this funeral home (grouped by category)
    $allItems = InventoryItem::where('funeral_home_id', $booking->funeral_home_id)
        ->where('status', 'available')
        ->where('quantity', '>', 0)
        ->get()
        ->groupBy('inventory_category_id');

    // Asset categories WITH price (consistent with client controller)
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

    $packageName = $booking->package->name ?? '';

    // Package items total (consumables)
    $itemTotal = $booking->package
        ? $booking->package->items->sum(function($item) {
            $qty = $item->pivot->quantity ?? 1;
            $price = $item->selling_price ?? $item->price ?? 0;
            return $qty * $price;
        })
        : 0;

    // Asset category IDs in package items (to avoid double-counting)
    $assetCategoryIdsInItems = $booking->package
        ? $booking->package->items->pluck('inventory_category_id')->unique()->toArray()
        : [];

    // Asset categories not already included in package items
    $extraAssetCategories = \DB::table('package_asset_categories')
        ->where('service_package_id', $booking->package_id)
        ->whereNotIn('inventory_category_id', $assetCategoryIdsInItems)
        ->get();

    $extraAssetCategoryTotal = $extraAssetCategories->sum('price');

    // Total calculation (with custom if approved)
    $totalAmount = ($booking->customizedPackage && $booking->customizedPackage->status === 'approved')
        ? $booking->customizedPackage->custom_total_price
        : ($itemTotal + $extraAssetCategoryTotal);

    // Discount/final
    $discountAmount = old('discount_amount',
        $booking->discount_amount
            ?? ($booking->detail->discount_amount ?? 0)
    );

    $isDiscountBeneficiary = old('is_discount_beneficiary',
        $booking->is_discount_beneficiary
            ?? ($booking->detail->is_discount_beneficiary ?? '')
    );

    $finalAmount = old('final_amount',
        $booking->final_amount
            ?? ($totalAmount - ($discountAmount ?? 0))
    );

    // Agent assignment autofill fields
    $needAgentValue = old('need_agent', $booking->agentAssignment->need_agent ?? '');
    $agentTypeValue = old('agent_type', $booking->agentAssignment->agent_type ?? '');
    $clientAgentEmailValue = old('client_agent_email', $booking->agentAssignment->client_agent_email ?? '');

    return view('agent.bookings.editBooking', [
        'booking'              => $booking,
        'customized'           => $customized,
        'customItems'          => $customItems,
        'allItems'             => $allItems,
        'assetCategories'      => $assetCategories, // always include price field
        'agentAssignment'      => $booking->agentAssignment,
        'packageName'          => $packageName,
        'totalAmount'          => $totalAmount,
        'discountAmount'       => $discountAmount,
        'isDiscountBeneficiary'=> $isDiscountBeneficiary,
        'finalAmount'          => $finalAmount,
        'needAgentValue'       => $needAgentValue,
        'agentTypeValue'       => $agentTypeValue,
        'clientAgentEmailValue'=> $clientAgentEmailValue,
    ]);
}




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

    // Validation (for agent, discount fields NOT required here)
    $validated = $request->validate([
        'wake_start_date'         => 'nullable|date',
        'wake_end_date'           => 'nullable|date|after_or_equal:wake_start_date',
        'interment_cremation_date'=> 'nullable|date',
        'cemetery_or_crematory'   => 'nullable|string|max:255',
        'has_plot_reserved'       => 'nullable|boolean',
        'attire'                  => 'nullable|string|max:255',
        'need_agent'              => 'nullable|in:yes,no',
        'agent_type'              => 'nullable|in:client,parlor',
        'client_agent_email'      => 'nullable|email',
        'post_services'           => 'nullable|string|max:255',
        'payment_method'          => 'required|in:full,installment',
        'amount'                  => 'required|numeric|min:0',
        'installment_duration'    => 'nullable|integer|min:2|max:36',
        'death_certificate_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20240',
    ]);

    Log::info('Agent Booking Update: Validated request data', $validated);

    // --- Update BookingDetail ---
    $detail = $booking->detail;
    if (!$detail) {
        $detail = new \App\Models\BookingDetail();
        $detail->booking_id = $booking->id;
        Log::info('Agent Booking Update: Created new BookingDetail record');
    }

    $detail->wake_start_date           = $validated['wake_start_date'] ?? null;
    $detail->wake_end_date             = $validated['wake_end_date'] ?? null;
    $detail->interment_cremation_date  = $validated['interment_cremation_date'] ?? null;
    $detail->cemetery_or_crematory     = $validated['cemetery_or_crematory'] ?? null;
    $detail->has_plot_reserved         = $validated['has_plot_reserved'] ?? null;
    $detail->attire                    = $validated['attire'] ?? null;
    $detail->post_services             = $validated['post_services'] ?? null;
    $detail->amount                    = $validated['amount'];

    // Handle death certificate upload
    if ($request->hasFile('death_certificate_file')) {
        if ($detail->death_certificate_path && \Storage::disk('public')->exists($detail->death_certificate_path)) {
            \Storage::disk('public')->delete($detail->death_certificate_path);
        }
        $file = $request->file('death_certificate_file');
        $path = $file->store('death_certificates', 'public');
        $detail->death_certificate_path = $path;
        Log::info('Agent Booking Update: Saved death certificate file', ['death_certificate_path' => $path]);
    }
    $detail->save();
    Log::info('Agent Booking Update: BookingDetail saved', $detail->toArray());

    // --- Update the booking main record ---
    $booking->final_amount = $validated['amount'];
    $booking->save();
    Log::info('Agent Booking Update: Booking saved', $booking->toArray());

    // --- Update/insert Agent Assignment ---
    $agentAssignment = [
        'booking_id'         => $booking->id,
        'need_agent'         => $validated['need_agent'] ?? null,
        'agent_type'         => $validated['agent_type'] ?? null,
        'client_agent_email' => $validated['client_agent_email'] ?? null,
    ];

    if ($booking->agentAssignment) {
        $booking->agentAssignment->update($agentAssignment);
        Log::info('Agent Booking Update: AgentAssignment updated', $agentAssignment);
    } else {
        BookingAgent::create($agentAssignment);
        Log::info('Agent Booking Update: AgentAssignment created', $agentAssignment);
    }

    // --- Payment records ---
    $booking->payments()->delete();
    Log::info('Agent Booking Update: Deleted previous payment records');

    if ($validated['payment_method'] === 'full') {
        $payment = Payment::create([
            'booking_id'     => $booking->id,
            'amount'         => $validated['amount'],
            'method'         => 'full',
            'installment_no' => null,
            'due_date'       => now()->addDays(7),
            'status'         => 'pending',
            'notes'          => 'Full payment for booking.',
        ]);
        Log::info('Agent Booking Update: Created full payment', $payment->toArray());
    } else {
        $duration = $validated['installment_duration'] ?? 12;
        $installmentAmount = round($validated['amount'] / $duration, 2);
        $dueDate = now()->addDays(7);

        for ($i = 1; $i <= $duration; $i++) {
            $payment = Payment::create([
                'booking_id'     => $booking->id,
                'amount'         => $installmentAmount,
                'method'         => 'installment',
                'installment_no' => $i,
                'due_date'       => $dueDate->copy()->addMonths($i - 1),
                'status'         => 'pending',
                'notes'          => "Installment {$i} of {$duration}",
            ]);
            Log::info("Agent Booking Update: Created installment payment #$i", $payment->toArray());
        }
    }

    $booking->status = 'for_initial_review';
    $booking->save();
    Log::info('Agent Booking Update: Status set to for_initial_review and saved');

    // --- Notify parties ---
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

    // Always get the amount from booking_details
    $totalAmount = $booking->detail->amount ?? 0;
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
    $booking = \App\Models\Booking::with(['detail', 'package.items', 'customizedPackage', 'client', 'funeralHome', 'agent'])->findOrFail($bookingId);

    // Agent authorization check (matches your previous logic)
    $agentId = auth()->id();
    $isAssignedAgent = $booking->agent_user_id === $agentId
        || ($booking->bookingAgent && $booking->bookingAgent->agent_user_id === $agentId);
    if (auth()->user()->role !== 'agent' || !$isAssignedAgent) {
        abort(403, 'Unauthorized access');
    }

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

        // Deceased Image
        'deceased_image' => [
            function($attribute, $value, $fail) use ($request, $booking) {
                $removeRequested = $request->input('remove_deceased_image') === "1";
                $hasFile = $request->hasFile('deceased_image');
                $hasExisting = $booking->detail && $booking->detail->deceased_image;

                if (!$hasFile && !$hasExisting && !$removeRequested) {
                    $fail('The deceased image is required.');
                }
            },
            'nullable',
            'image',
            'max:40960'
        ],
        'remove_deceased_image' => 'nullable|in:0,1',

        // B. Documents
        'death_cert_registration_no'     => 'nullable|string|max:100',
        'death_cert_released_to'         => 'nullable|string|max:100',
        'death_cert_released_date'       => 'nullable|date',
        'death_cert_released_signature'     => 'nullable|string',
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

        // D. Service, Amount, Fees (except service/amount)
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

    // Always autofill Service & Amount from package or customization (ignore user input)
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
    $detail = $booking->detail ?: new \App\Models\BookingDetail(['booking_id' => $booking->id]);
    $detail->fill($validated);

    // Always overwrite these (enforced by system)
    $detail->service = $serviceName;
    $detail->amount = $validated['amount'] ?? 0;
    $detail->booking_id = $booking->id;

    // Explicitly set signature image fields (in case not fillable)
    $detail->death_cert_released_signature         = $validated['death_cert_released_signature'] ?? null;
    $detail->funeral_contract_released_signature   = $validated['funeral_contract_released_signature'] ?? null;
    $detail->official_receipt_released_signature   = $validated['official_receipt_released_signature'] ?? null;
    $detail->certifier_signature_image             = $validated['certifier_signature_image'] ?? null;

    // Handle deceased image upload/removal
    $removeImageRequested = $request->input('remove_deceased_image') === "1";
    if ($removeImageRequested) {
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $detail->deceased_image = null;
    } elseif ($request->hasFile('deceased_image')) {
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $uploaded = $request->file('deceased_image')->store('deceased_images', 'public');
        $detail->deceased_image = $uploaded;
    }
    // else: Keep the existing image if not removed and no new file

    $detail->save();

    // Optionally update status (as needed for agent flow)
    // Example: Set to 'pending_payment' after agent updates info
    if (in_array($booking->status, ['for_review', 'in_progress', 'confirmed', 'pending_payment'])) {
        $booking->status = 'pending_payment';
        $booking->save();
    }

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

        // Allow only client, funeral, or agent
        if (!in_array($currentUser->role, ['client', 'funeral', 'agent'])) {
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
            $referenceNumber = $result['data']['attributes']['reference_number'] ?? null; // get the short code

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
        return view('agent.bookings.payment', compact('booking'));
    }

    public function createPaymentLink(Request $request, $bookingId)
    {
        $booking = \App\Models\Booking::findOrFail($bookingId);
        $amount = $request->input('amount') * 100; // centavos

        $secretKey = config('services.paymongo.secret'); // store your key in config/services.php

        $payload = [
            "data" => [
                "attributes" => [
                    "amount" => $amount,
                    "currency" => "PHP",
                    "description" => "Payment for Package: " . $booking->package->name,
                    "remarks" => "Booking ID: $booking->id",
                ]
            ]
        ];

        $response = Http::withHeaders([
            "Authorization" => "Basic " . base64_encode($secretKey . ":"),
            "Content-Type" => "application/json"
        ])->post("https://api.paymongo.com/v1/links", $payload);

        $result = $response->json();

        if (isset($result['data']['attributes']['checkout_url'])) {
            return redirect($result['data']['attributes']['checkout_url']);
        } else {
            return back()->withErrors(['payment' => 'Error creating payment link: ' . json_encode($result)]);
        }
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
                return redirect()->route('agent.dashboard')
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
                                'success' => route('agent.bookings.paymongo.success', $booking->id),
                                'failed' => route('agent.bookings.paymongo.failed', $booking->id),
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

    return view('agent.bookings.payment', [
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

        // ==== Notify Funeral Parlor ====
        if ($booking->funeralHome) {
            $packageName = $booking->package->name ?? 'the package';
            $clientName  = $booking->client->name ?? 'the client';
            $parlorMsg = "Payment for booking <b>#{$booking->id}</b> ({$packageName}) by <b>{$clientName}</b> has been <b>SUCCESSFULLY PAID.</b> Please proceed to final approval and service scheduling.";
            $booking->funeralHome->notify(new \App\Notifications\BookingStatusChanged($booking, $parlorMsg, 'parlor'));
        }

        // ==== Notify Funeral Parlor (optional, but good practice) ====
        if ($booking->funeralHome) {
            $parlorMsg = "Payment for booking <b>#{$booking->id}</b> ({$packageName}) by <b>{$clientName}</b> has been <b>SUCCESSFULLY PAID.</b> Please proceed to final approval and service scheduling.";
            $booking->funeralHome->notify(new \App\Notifications\BookingStatusChanged($booking, $parlorMsg, 'parlor'));
        }
    }

    return redirect()->route('agent.dashboard')->with('success', 'Payment successful!');
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
        $booking->status = 'for_initial_review'; // or whatever previous status
        $booking->save();
    }

    return redirect()
        ->route('agent.bookings.payment', $bookingId)
        ->withErrors(['payment' => 'GCash payment was cancelled or failed. Please try again.']);
}
*/
}
