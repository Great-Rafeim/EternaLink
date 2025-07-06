<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\CustomizedPackage;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\BookingAgent;
use Illuminate\Support\Facades\Log;
use Paymongo\Paymongo;
 use Illuminate\Support\Facades\Http;


class BookingContinueController extends Controller
{

public function edit($bookingId)
{
    $booking = Booking::with([
        'funeralHome',
        'package.items.category',
        'customizedPackage.items.inventoryItem.category',
        'customizedPackage.items.substituteFor',
        'agentAssignment',
        'detail',
    ])->findOrFail($bookingId);

    // Security: Only allow the client to access their booking
    if ($booking->client_user_id !== auth()->id()) abort(403);

    // Customized package handling
    $customized = $booking->customizedPackage
        ?? CustomizedPackage::firstOrCreate(
            ['booking_id' => $booking->id],
            ['original_package_id' => $booking->package_id]
        );

    // Prepares a list of custom items for the view (if needed)
    $customItems = $customized->items()->get()->map(function ($item) {
        return [
            'item_id'        => $item->inventory_item_id,
            'quantity'       => $item->quantity,
            'substitute_for' => $item->substitute_for,
        ];
    });

    // All available inventory items for this funeral home
    $allItems = InventoryItem::where('funeral_home_id', $booking->funeral_home_id)
        ->where('status', 'available')
        ->where('quantity', '>', 0)
        ->get()
        ->groupBy('inventory_category_id');

    // --- Asset Categories WITH PRICE ---
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

    // --- Package Item Total (consumables) ---
    $itemTotal = $booking->package
        ? $booking->package->items->sum(function($item) {
            $qty = $item->pivot->quantity ?? 1;
            $price = $item->selling_price ?? $item->price ?? 0;
            return $qty * $price;
        })
        : 0;

    // Find asset categories included as items (to avoid double-counting in extras)
    $assetCategoryIdsInItems = $booking->package
        ? $booking->package->items->pluck('inventory_category_id')->unique()->toArray()
        : [];

    // --- Extra Asset Categories (not covered by items) ---
    $extraAssetCategories = \DB::table('package_asset_categories')
        ->where('service_package_id', $booking->package_id)
        ->whereNotIn('inventory_category_id', $assetCategoryIdsInItems)
        ->get();

    $extraAssetCategoryTotal = $extraAssetCategories->sum('price');

    // --- Total Amount Calculation ---
    $totalAmount = ($booking->customizedPackage && $booking->customizedPackage->status === 'approved')
        ? $booking->customizedPackage->custom_total_price
        : ($itemTotal + $extraAssetCategoryTotal);

    // --- Discount/Final ---
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

    // Autofill agent fields
    $needAgentValue = old('need_agent', $booking->agentAssignment->need_agent ?? '');
    $agentTypeValue = old('agent_type', $booking->agentAssignment->agent_type ?? '');
    $clientAgentEmailValue = old('client_agent_email', $booking->agentAssignment->client_agent_email ?? '');

    return view('client.bookings.continue.edit', [
        'booking'              => $booking,
        'customized'           => $customized,
        'customItems'          => $customItems,
        'allItems'             => $allItems,
        'assetCategories'      => $assetCategories, // This now contains price property!
        'agentAssignment'      => $booking->agentAssignment,
        'packageName'          => $packageName,
        'totalAmount'          => $totalAmount,
        'discountAmount'       => $discountAmount,
        'isDiscountBeneficiary'=> $isDiscountBeneficiary,
        'finalAmount'          => $finalAmount,
        // Agent
        'needAgentValue'       => $needAgentValue,
        'agentTypeValue'       => $agentTypeValue,
        'clientAgentEmailValue'=> $clientAgentEmailValue,
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
        $booking->status = 'pending_payment';
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

return redirect()->route('client.bookings.payment', $booking->id)
    ->with('success', 'Booking details saved. Please continue to fill up the personal details.');

}



/////////////////////////////////////

    // PHASE 3 FORM: Info of the Dead
public function info($bookingId)
{
    $booking = \App\Models\Booking::with([
        'detail',
        'package',           // service_package relation
        'customizedPackage', // customized_packages relation
    ])->findOrFail($bookingId);

    if ($booking->client_user_id !== auth()->id()) abort(403);

    // Always get the amount from booking_details
    $totalAmount = $booking->detail->amount ?? 0;

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

    // Validate including the new fields
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
        'final_amount'            => 'required|numeric|min:0',
        'installment_duration'    => 'nullable|integer|min:2|max:36',
        'death_certificate_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20240',

        // Discount/payment fields (from booking table)
        'is_discount_beneficiary' => 'required|in:0,1',
        'discount_amount'         => 'nullable|numeric|min:0',
        'id_proof_file'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20240',
    ]);

    // ---- Log validated request for debugging ----
    Log::info('Booking Update: Validated request data', $validated);

    $booking->is_discount_beneficiary = $validated['is_discount_beneficiary'];
    $booking->discount_amount = $validated['discount_amount'] ?? 0;
    $booking->final_amount = $validated['final_amount'];

    Log::info('Booking Update: Setting booking table fields', [
        'is_discount_beneficiary' => $booking->is_discount_beneficiary,
        'discount_amount' => $booking->discount_amount,
        'final_amount' => $booking->final_amount,
    ]);

    // Handle ID proof upload for discount
    if ($request->hasFile('id_proof_file')) {
        if ($booking->discount_proof_path && \Storage::disk('public')->exists($booking->discount_proof_path)) {
            \Storage::disk('public')->delete($booking->discount_proof_path);
        }
        $file = $request->file('id_proof_file');
        $path = $file->store('discount_proofs', 'public');
        $booking->discount_proof_path = $path;
        Log::info('Booking Update: Saved discount proof file', ['discount_proof_path' => $path]);
    }

    $booking->save();
    Log::info('Booking Update: Booking saved', $booking->toArray());

    // Update other booking details in the details table (not discount stuff)
    $detail = $booking->detail;
    if (!$detail) {
        $detail = new \App\Models\BookingDetail();
        $detail->booking_id = $booking->id;
        Log::info('Booking Update: Created new BookingDetail record');
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
        Log::info('Booking Update: Saved death certificate file', ['death_certificate_path' => $path]);
    }
    $detail->save();

    Log::info('Booking Update: BookingDetail saved', $detail->toArray());

    // Agent assignment
    $agentAssignment = [
        'booking_id'         => $booking->id,
        'need_agent'         => $validated['need_agent'] ?? null,
        'agent_type'         => $validated['agent_type'] ?? null,
        'client_agent_email' => $validated['client_agent_email'] ?? null,
    ];

    if ($booking->agentAssignment) {
        $booking->agentAssignment->update($agentAssignment);
        Log::info('Booking Update: AgentAssignment updated', $agentAssignment);
    } else {
        BookingAgent::create($agentAssignment);
        Log::info('Booking Update: AgentAssignment created', $agentAssignment);
    }

    // Payment records
    $booking->payments()->delete();
    Log::info('Booking Update: Deleted previous payment records');

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
        Log::info('Booking Update: Created full payment', $payment->toArray());
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
            Log::info("Booking Update: Created installment payment #$i", $payment->toArray());
        }
    }

    $booking->status = 'for_initial_review';
    $booking->save();
    Log::info('Booking Update: Status set to for_initial_review and saved');

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

    return redirect()->route('client.dashboard', $booking->id)
        ->with('success', 'Booking details saved. Please continue to fill up the personal details.');
}

//////////////////////////////
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

        // Only allow users who are clients or funeral parlor staff
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
        return view('client.bookings.payment', compact('booking'));
    }




























/*
public function payWithPayMongo(Request $request, $bookingId)
{
    $booking = Booking::with(['payments'])->findOrFail($bookingId);
    if ($booking->client_user_id !== auth()->id()) abort(403);

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
            // CREATE PaymentIntent (BASIC AUTH)
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
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

            // ATTACH Payment Method (BASIC AUTH)
            $attach = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
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
                // Save as paid
                $booking->payments()->create([
                    'amount' => $amount,
                    'method' => 'paymongo_card',
                    'status' => 'paid',
                    'notes'  => 'Paid via card (PayMongo JS)',
                ]);
                $booking->status = 'paid';
                $booking->save();
                return redirect()->route('client.dashboard')
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
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->post('https://api.paymongo.com/v1/sources', [
                    'data' => [
                        'attributes' => [
                            'amount' => $amountInCents,
                            'redirect' => [
                                'success' => route('client.bookings.paymongo.success', $booking->id),
                                'failed' => route('client.bookings.paymongo.failed', $booking->id),
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

            // Save payment record as pending
            $booking->payments()->create([
                'amount' => $amount,
                'method' => 'paymongo_gcash',
                'status' => 'pending',
                'notes' => 'GCash payment initiated. Awaiting confirmation.',
                'reference_id' => $source['id'], // save source id for webhook matching
            ]);
            $booking->status = 'pending_payment';
            $booking->save();

            // Redirect to GCash QR/authorization page
            if ($redirectUrl) {
                return redirect($redirectUrl);
            } else {
                return back()->withErrors(['payment' => 'Failed to create GCash source.']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['payment' => 'GCash payment failed: ' . $e->getMessage()]);
        }
    }

    // Fallback
    return back()->withErrors(['payment' => 'Invalid payment method.']);
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

        // ==== Notify Assigned Agent (if any) ====
        if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
            $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
            if ($agentUser) {
                $packageName = $booking->package->name ?? 'the package';
                $clientName  = $booking->client->name ?? 'the client';
                $parlorName  = $booking->funeralHome->name ?? 'Funeral Parlor';
                $agentMsg = "Your client <b>{$clientName}</b> has <b>PAID</b> for booking <b>#{$booking->id}</b> ({$packageName}) at <b>{$parlorName}</b>. The funeral parlor will now proceed with the next steps.";
                $agentUser->notify(new \App\Notifications\BookingStatusChanged($booking, $agentMsg, 'agent'));
            }
        }
    }

    return redirect()->route('client.dashboard')->with('success', 'Payment successful!');
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
        ->route('client.bookings.payment', $bookingId)
        ->withErrors(['payment' => 'GCash payment was cancelled or failed. Please try again.']);
}

*/



}
