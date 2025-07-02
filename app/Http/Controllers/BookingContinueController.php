<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\CustomizedPackage;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\BookingAgent;

class BookingContinueController extends Controller
{
public function edit($bookingId)
{
    $booking = Booking::with([
        'funeralHome',
        'package.items.category',
        'customizedPackage',
        'agentAssignment'
    ])->findOrFail($bookingId);

    if ($booking->client_user_id !== auth()->id()) abort(403);

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

    return view('client.bookings.continue.edit', [
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



//////////////////////////////////////////////////////////////////////////
public function updateInfo(Request $request, $bookingId)
{
    $booking = Booking::with(['detail', 'package.items', 'customizedPackage'])->findOrFail($bookingId);
    if ($booking->client_user_id !== auth()->id()) abort(403);

    // Validate all fields, including the image upload/remove logic
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
            // Only required if new or not already present
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
        'death_cert_released_signature'     => 'nullable|string', // <--- base64
        'funeral_contract_no'            => 'nullable|string|max:100',
        'funeral_contract_released_to'   => 'nullable|string|max:100',
        'funeral_contract_released_date' => 'nullable|date',
        'funeral_contract_released_signature'=> 'nullable|string', // <--- base64
        'official_receipt_no'            => 'nullable|string|max:100',
        'official_receipt_released_to'   => 'nullable|string|max:100',
        'official_receipt_released_date' => 'nullable|date',
        'official_receipt_released_signature'=> 'nullable|string', // <--- base64

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
        'certifier_signature_image'=> 'nullable|string', // <--- base64
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
    $detail = $booking->detail ?: new BookingDetail(['booking_id' => $booking->id]);
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
        // Remove the image from storage if present
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $detail->deceased_image = null;
    } elseif ($request->hasFile('deceased_image')) {
        // Store new image and delete old one if present
        if ($detail->deceased_image && \Storage::disk('public')->exists($detail->deceased_image)) {
            \Storage::disk('public')->delete($detail->deceased_image);
        }
        $uploaded = $request->file('deceased_image')->store('deceased_images', 'public');
        $detail->deceased_image = $uploaded;
    }
    // else: Keep the existing image if not removed and no new file

    $detail->save();

    // Change status after submission (PHASE 3) — e.g. from 'ongoing', 'in_progress', or 'confirmed' to 'for_review'
    if (in_array($booking->status, ['ongoing', 'in_progress', 'confirmed'])) {
        $booking->status = 'for_review';
        $booking->save();
    }

    // Notify funeral parlor with detailed info
    if ($booking->funeralHome && $booking->client) {
        $clientName = $booking->client->name ?? 'Client';
        $funeralHomeName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $bookingNumber = $booking->id;

        $message = "Booking #{$bookingNumber} has been submitted for final review by {$clientName}. "
                . "This booking is under {$funeralHomeName}. Please review the details and take action as needed.";

        $booking->funeralHome->notify(
            new \App\Notifications\BookingStatusChanged($booking, $message)
        );
    }
    // Notify assigned agent (if any)
    $agent = $booking->agent;
    if ($agent) {
        $agentMessage = "Booking #{$booking->id} is now awaiting your review. "
                    . "Client {$booking->client->name} has submitted the required details for the info of deceased. Please check the booking for further action.";
        $agent->notify(
            new \App\Notifications\BookingStatusChanged($booking, $agentMessage)
        );
    }

    return redirect()
        ->route('client.dashboard')
        ->with('success', 'Personal & service details saved. Please wait for the funeral parlor to review.');
}



/////////////////////////////////////

    // PHASE 3 FORM: Info of the Dead
public function info($bookingId)
{
    $booking = \App\Models\Booking::with([
        'detail',
        'package',         // service_package relation
        'customizedPackage', // customized_packages relation
    ])->findOrFail($bookingId);

    if ($booking->client_user_id !== auth()->id()) abort(403);

    // Correct amount logic
    if ($booking->customized_package_id) {
        // Always check the relationship exists to avoid null error
        $totalAmount = $booking->customizedPackage->custom_total_price ?? 0;
    } else {
        $totalAmount = $booking->package->total_price ?? 0;
    }

    return view('client.bookings.continue.info-of-the-dead', [
        'booking'     => $booking,
        'detail'      => $booking->detail,
        'packageName' => $booking->package->name ?? '',
        'totalAmount' => $totalAmount,
    ]);
}

///////////////////////////////////////////////////

public function update(Request $request, $bookingId)
{
    $booking = Booking::with(['detail', 'customizedPackage', 'package.items', 'agentAssignment', 'funeralHome'])
        ->findOrFail($bookingId);

    if ($booking->client_user_id !== auth()->id()) abort(403);

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

    // Notify funeral parlor
    if ($booking->funeralHome && $booking->client) {
        $parlorName = $booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $booking->client->name ?? 'Client';
        $bookingNumber = $booking->id;

        $msg = "Booking #{$bookingNumber} has been submitted for initial review by {$clientName}. "
            . "Please review and set other fees if applicable. [Funeral Parlor: {$parlorName}]";

        $booking->funeralHome->notify(
            new \App\Notifications\BookingStatusChanged($booking, $msg)
        );
    }
    // Notify assigned agent (if any)
    $agent = $booking->agent;
    if ($agent) {
        $agentMsg = "Client {$clientName} has submitted details for booking #{$bookingNumber}.";
        $agent->notify(
            new \App\Notifications\BookingStatusChanged($booking, $agentMsg)
        );
    }

    // Redirect to info-of-the-dead form (Phase 3)
    return redirect()->route('client.dashboard', $booking->id)
        ->with('success', 'Booking details saved. Please continue to fill up the personal details.');
}




}
