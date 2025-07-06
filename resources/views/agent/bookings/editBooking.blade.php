<x-agent-layout>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
        <br>
        <b>Next:</b> Please fill up the deceased’s personal details in the next form.
    </div>
@endif

{{-- ======================== 1. Package Customization ======================== --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">1. Package Customization</div>
    <div class="card-body">
        <p class="text-muted mb-3">
            You may adjust quantities or substitute alternatives <b>only for specific items</b>.<br>
            <span class="text-info">
                Bookable assets (e.g. vehicles, equipment, venues) are required for your service and <b>cannot be modified</b> in this form.<br>
            </span>
        </p>

        @php
            $packageItemIds = $booking->package->items->pluck('id')->toArray();
        @endphp

        {{-- Package Customization Form (POST to /customize/send) --}}
        <form action="{{ route('agent.bookings.customize.send', $booking->id) }}" method="POST">
            @csrf
            <h6 class="fw-bold mb-2">Your Package</h6>
            <div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Category</th>
            <th>Item</th>
            <th>Brand</th>
            <th>Price</th>
            <th>Default Qty</th>
            <th>Your Qty</th>
            <th>Substitute Item</th>
            <th>Substitute Price</th>
            <th>Total</th> {{-- New --}}
        </tr>
    </thead>
    <tbody>
    @foreach($booking->package->items as $item)
        @php
            $isAsset = $item->category->is_asset ?? false;
            $custom = collect($customItems)->firstWhere('item_id', $item->id) ?? [];
            $selectedItemId = $custom['substitute_for'] ?? $item->id;
            $inputQty = $custom['quantity'] ?? $item->pivot->quantity;
            $categoryItems = $allItems[$item->inventory_category_id] ?? collect();
            $availableStock = $categoryItems->where('id', $selectedItemId)->first()->quantity ?? $item->quantity;
            $disabled = $isAsset ? 'disabled' : '';
            $selectedItem = $categoryItems->where('id', $selectedItemId)->first() ?? $item;
            $subPrice = $selectedItem->selling_price ?? 0;
            $rowTotal = $subPrice * $inputQty;
        @endphp
        <tr @if($isAsset) class="table-secondary" @endif>
            <td>
                {{ $item->category->name ?? '-' }}
                @if($isAsset)
                    <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                @endif
            </td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->brand ?? '-' }}</td>
            <td>₱{{ number_format($item->selling_price ?? 0, 2) }}</td>
            <td>
                <input type="number" class="form-control-plaintext" readonly value="{{ $item->pivot->quantity }}">
            </td>
            <td style="width:120px;">
                <input type="number"
                    min="1"
                    max="{{ $availableStock }}"
                    class="form-control customization-input qty-input"
                    name="custom_items[{{ $item->id }}][quantity]"
                    value="{{ old("custom_items.{$item->id}.quantity", $inputQty) }}"
                    data-default="{{ $item->pivot->quantity }}"
                    data-item-id="{{ $item->id }}"
                    {{ $disabled }}>
            </td>
            <td>
                <select name="custom_items[{{ $item->id }}][substitute_for]"
                        class="form-select customization-input substitute-select"
                        data-default="{{ $item->id }}"
                        data-item-id="{{ $item->id }}"
                        {{ $disabled }}>
                    <option value="{{ $item->id }}" data-price="{{ $item->selling_price }}" {{ $selectedItemId == $item->id ? 'selected' : '' }}>
                        -- {{ $item->name }} (Default) --
                    </option>
                    @foreach($categoryItems as $alt)
                        @if(
                            !$isAsset &&
                            $alt->id != $item->id &&
                            !in_array($alt->id, $packageItemIds)
                        )
                            <option value="{{ $alt->id }}" data-price="{{ $alt->selling_price }}" {{ $selectedItemId == $alt->id ? 'selected' : '' }}>
                                {{ $alt->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </td>
            <td>
                <span id="substitutePriceDisplay_{{ $item->id }}">
                    {{ $selectedItemId == $item->id ? '-' : '₱' . number_format($subPrice, 2) }}
                </span>
            </td>
            <td>
                <span id="rowTotalDisplay_{{ $item->id }}">
                    ₱{{ number_format($rowTotal, 2) }}
                </span>
            </td>
        </tr>
    @endforeach

    {{-- Asset categories without items --}}
    @foreach($assetCategories ?? [] as $assetCategory)
        @if(!$booking->package->items->where('inventory_category_id', $assetCategory->id)->count())
        <tr class="table-secondary">
            <td>
                {{ $assetCategory->name }}
                <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
            </td>
            <td colspan="8" class="text-muted">Included as a required asset (exact item to be assigned by parlor)</td>
        </tr>
        @endif
    @endforeach
    </tbody>
</table>

            

            </div>

            <small class="text-muted">
                If you need more than available, please contact the funeral parlor directly.
            </small>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" id="sendCustomizationBtn" class="btn btn-success">
                    Send Customization Request
                </button>
            </div>
        </form>

        {{-- CUSTOMIZED PACKAGE TABLE (Only if exists) --}}
{{-- CUSTOMIZED PACKAGE TABLE (Only if exists) --}}
@if(in_array($customized->status, ['pending', 'approved', 'denied']) && $customized->items()->count())
    <div class="mt-5">
        <h6 class="fw-bold mb-2">Your Customized Package</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Category Price</th>
                        <th>Item</th>
                        <th>Item Price</th>
                        <th>Brand</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Collect asset category prices for quick lookup (id => price)
                        $assetCategoryPrices = collect($assetCategories ?? [])->pluck('price', 'id')->toArray();
                        // Track which asset categories are already shown
                        $customizedAssetCategoryIds = $customized->items
                            ->filter(fn($item) => $item->inventoryItem && ($item->inventoryItem->category->is_asset ?? false))
                            ->pluck('inventoryItem.category.id')
                            ->unique()
                            ->toArray();
                    @endphp
                    @foreach($customized->items as $customItem)
                        @php
                            $inventory = $customItem->inventoryItem;
                            $category = $inventory->category->name ?? '-';
                            $isAsset = $inventory->category->is_asset ?? false;
                            $original = $customItem->substitute_for
                                ? App\Models\InventoryItem::find($customItem->substitute_for)
                                : null;
                            $categoryId = $inventory->category->id ?? null;
                            $categoryPrice = $isAsset && $categoryId && isset($assetCategoryPrices[$categoryId])
                                ? $assetCategoryPrices[$categoryId]
                                : null;
                        @endphp
                        <tr @if($isAsset) class="table-secondary" @endif>
                            <td>
                                {{ $category }}
                                @if($isAsset)
                                    <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                                @endif
                            </td>
                            <td>
                                @if($isAsset && $categoryPrice)
                                    ₱{{ number_format($categoryPrice, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{ $inventory->name }}
                                @if($original)
                                    <br><small class="text-muted">(Substitute for: {{ $original->name }})</small>
                                @endif
                            </td>
                            <td>
                                @if(isset($inventory->selling_price))
                                    ₱{{ number_format($inventory->selling_price, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $inventory->brand ?? '-' }}</td>
                            <td>{{ $customItem->quantity }}</td>
                        </tr>
                    @endforeach

                    {{-- Show asset categories not in customized items --}}
                    @foreach($assetCategories ?? [] as $assetCategory)
                        @if(!in_array($assetCategory->id, $customizedAssetCategoryIds))
                            <tr class="table-secondary">
                                <td>
                                    {{ $assetCategory->name }}
                                    <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                                </td>
                                <td>
                                    @if($assetCategory->price)
                                        ₱{{ number_format($assetCategory->price, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td colspan="4" class="text-muted">
                                    Included as a required asset (exact item to be assigned by parlor)
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif


        {{-- Status badge --}}
        @if($customized->status === 'pending')
            <span class="badge bg-warning text-dark mt-3">Waiting for funeral parlor approval...</span>
        @elseif($customized->status === 'approved')
            <span class="badge bg-success mt-3">Customization Approved — you may update again if needed.</span>
        @elseif($customized->status === 'denied')
            <span class="badge bg-danger mt-3">Customization Denied — you may revise and resend.</span>
        @endif
    </div>
</div>

{{-- Customization script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Only disable the inputs if status is pending (not the button!)
    @if($customized->status === 'pending')
        document.querySelectorAll('.customization-input').forEach(el => el.disabled = true);
    @endif
});


document.addEventListener('DOMContentLoaded', function () {
    // Change price and total when substitute or qty changes
    document.querySelectorAll('.substitute-select, .qty-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const rowItemId = this.dataset.itemId;
            const select = document.querySelector('.substitute-select[data-item-id="'+rowItemId+'"]');
            const qtyInput = document.querySelector('.qty-input[data-item-id="'+rowItemId+'"]');
            const priceSpan = document.getElementById('substitutePriceDisplay_' + rowItemId);
            const rowTotalSpan = document.getElementById('rowTotalDisplay_' + rowItemId);

            let selectedOption = select.options[select.selectedIndex];
            let price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            let qty = parseInt(qtyInput.value) || 1;

            // Update Substitute Price
            priceSpan.textContent = select.value == rowItemId ? '-' : '₱' + price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Update Row Total
            rowTotalSpan.textContent = '₱' + (price * qty).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
    });
});
</script>


@php
    $disableNextForms = $customized->status === 'pending';
    $isAgent = auth()->user()->role === 'agent';
@endphp

{{-- ======================== 2-7. Continue Booking Main Form ======================== --}}
        {{-- 2-7. Continue Booking Main Form --}}



 <form action="{{ route('agent.bookings.updateBooking', $booking->id) }}" method="POST" id="continueBookingForm" enctype="multipart/form-data">
    @csrf

@php
    // Set today's date (Philippine time, or your server's timezone)
    $today = \Carbon\Carbon::now()->toDateString();
@endphp

{{-- 2. Wake and Burial Schedule --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">2. Wake and Interment Schedule</div>
    <div class="card-body row g-3">
        <div class="col-md-4">
            <label class="form-label">Wake Start Date <span class="text-danger">*</span></label>
            <input
                type="date"
                name="wake_start_date"
                class="form-control"
                min="{{ $today }}"
                value="{{ old('wake_start_date', $booking->detail->wake_start_date ?? '') }}"
                @if($disableNextForms) disabled @else required @endif
            >
        </div>
        <div class="col-md-4">
            <label class="form-label">Wake End Date <span class="text-danger">*</span></label>
            <input
                type="date"
                name="wake_end_date"
                class="form-control"
                min="{{ $today }}"
                value="{{ old('wake_end_date', $booking->detail->wake_end_date ?? '') }}"
                @if($disableNextForms) disabled @else required @endif
            >
        </div>
        <div class="col-md-4">
            <label class="form-label">Interment Date <span class="text-danger">*</span></label>
            <input
                type="date"
                name="interment_cremation_date"
                class="form-control"
                min="{{ $today }}"
                value="{{ old('interment_cremation_date', $booking->detail->interment_cremation_date ?? '') }}"
                @if($disableNextForms) disabled @else required @endif
            >
        </div>
    </div>
</div>


{{-- 3. Cemetery/Plot --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">
        3. Memorial Home Preferences
    </div>
    <div class="card-body row g-3">
        <div class="col-md-8">
            <label class="form-label">Preferred Cemetery/Columbarium</label>
            <div class="input-group">
                <input 
                    type="text" 
                    name="cemetery_or_crematory" 
                    class="form-control" 
                    value="{{ old('cemetery_or_crematory', $booking->detail->cemetery_or_crematory ?? '') }}"
                    @if($disableNextForms) disabled @endif
                    placeholder="Type preferred cemetery or crematory"
                >
                <a href="{{ route('client.cemeteries.index') }}" class="btn btn-outline-primary" tabindex="-1" target="_blank" rel="noopener">
                    Interment Date
                </a>
            </div>
        </div>
    </div>
</div>

    {{-- 4. Preferred Attire --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">4. Preferred Attire</div>
        <div class="card-body">
            <label class="form-label">Preferred Attire <span class="text-danger">*</span></label>
            @php
                $attireOptions = [
                    'Barong Tagalog'          => 'Barong Tagalog',
                    'White Dress'             => 'White Dress',
                    'Black Formal Wear'       => 'Black Formal Wear',
                    'White Shirt and Pants'   => 'White Shirt and Pants',
                    'Traditional Filipiniana' => 'Traditional Filipiniana',
                    'Religious Habit'         => 'Religious Habit',
                    'Casual Attire'           => 'Casual Attire',
                    'No Red Clothing'         => 'No Red Clothing',
                    'All White Attire'        => 'All White Attire',
                ];
                $selectedAttire = old('attire', $booking->detail->attire ?? '');
            @endphp
            <select name="attire" class="form-select"
                @if($disableNextForms) disabled @else required @endif>
                <option value="" {{ $selectedAttire == '' ? 'selected' : '' }}>-- Select Preferred Attire --</option>
                @foreach($attireOptions as $value => $label)
                    <option value="{{ $value }}" {{ $selectedAttire == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

{{-- 5. Agent Assistance (optional) --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">5. Agent Assistance</div>
    {{-- Fieldset always disabled --}}
    <fieldset disabled>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Do you need an agent to assist you?</label>
                <select name="need_agent" class="form-select" id="needAgent">
                    <option value="" {{ $needAgentValue === '' ? 'selected' : '' }}>--Select--</option>
                    <option value="yes" {{ $needAgentValue === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ $needAgentValue === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-4" id="agentTypeDiv">
                <label class="form-label">If yes, preferred agent:</label>
                <select name="agent_type" class="form-select" id="agentType">
                    <option value="" {{ $agentTypeValue === '' ? 'selected' : '' }}>--Select--</option>
                    <option value="parlor" {{ $agentTypeValue === 'parlor' ? 'selected' : '' }}>From Funeral Parlor</option>
                    <option value="client" {{ $agentTypeValue === 'client' ? 'selected' : '' }}>My Relative</option>
                </select>
            </div>
            <div class="col-md-4" id="clientAgentEmailDiv">
                <label class="form-label">Relative's Email</label>
                <input type="email" name="client_agent_email" class="form-control"
                    value="{{ $clientAgentEmailValue }}">
            </div>
        </div>
    </fieldset>
</div>




    {{-- 6. Post Services --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">6. Post Services</div>
        <div class="card-body">
            <label class="form-label">Post Services <span class="text-danger">*</span></label>
            @php
                $postServicesOptions = [
                    'After-care Support'    => 'After-care Support',
                    'Memorial Mass'         => 'Memorial Mass',
                    'Thanksgiving Service'  => 'Thanksgiving Service',
                    'Home Blessing'         => 'Home Blessing',
                    'Counseling'            => 'Counseling',
                    'Memorial Keepsakes'    => 'Memorial Keepsakes',
                    'Donation Arrangement'  => 'Donation Arrangement',
                    'None'                  => 'None',
                ];
                $selectedService = old('post_services', $booking->detail->post_services ?? '');
            @endphp
            <select name="post_services" class="form-select" @if($disableNextForms) disabled @else required @endif>
                <option value="" {{ $selectedService == '' ? 'selected' : '' }}>-- Select Post Service --</option>
                @foreach($postServicesOptions as $value => $label)
                    <option value="{{ $value }}" {{ $selectedService == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>


{{-- Senior Citizen / PWD Discount Section --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">7. Senior Citizen / PWD Discount</div>
    <div class="card-body">
        <label class="form-label">Are you a Senior Citizen or PWD Beneficiary? <span class="text-danger">*</span></label>
        <select name="is_discount_beneficiary" class="form-select" id="discountEligibility"
            @if($disableNextForms) disabled @else required @endif>
            <option value="" {{ $isDiscountBeneficiary === '' ? 'selected' : '' }}>-- Select --</option>
            <option value="1" {{ $isDiscountBeneficiary == 1 ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ $isDiscountBeneficiary === '0' ? 'selected' : '' }}>No</option>
        </select>
        <small class="text-muted mt-2 d-block">If eligible, a 20% discount will be automatically applied to the total amount.</small>

{{-- Document Upload Section --}}
<div id="discountDocumentDiv" class="mt-3" style="display: none;">
    <label class="form-label">Upload ID Proof (PDF, JPG, or PNG) <span class="text-danger">*</span></label>
    <input type="file" name="id_proof_file" class="form-control"
        accept="application/pdf,image/jpeg,image/png"
        @if($disableNextForms) disabled @endif
    >

    {{-- Show existing file from either Booking or BookingDetail --}}
    @php
        $idProofPath = $booking->discount_proof_path ?? ($booking->detail->id_proof_path ?? null);
    @endphp
    @if($idProofPath)
        <div class="mt-2">
            <a href="{{ asset('storage/' . $idProofPath) }}" target="_blank" class="btn btn-link">
                View Uploaded ID Proof
            </a>
        </div>
    @endif

    <div class="form-text">
        Maximum file size: 20 MB.
    </div>
</div>

    </div>
</div>

{{-- 7. Payment --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">7. Payment Details</div>
    <div class="card-body row g-3">
        {{-- Total Amount (Final Amount, what will be paid after discount) --}}
        <div class="col-md-4">
            <label class="form-label" id="totalAmountLabel">
                Total Amount (VAT Included) <span class="text-danger">*</span>
            </label>
            <input type="number" name="amount" id="totalAmount" class="form-control"
                value="{{ old('amount', $totalAmount) }}" readonly required>
            {{-- Hidden field to carry the ACTUAL discounted amount (final_amount) --}}
            <input type="hidden" name="final_amount" id="finalAmount"
                value="{{ $finalAmount }}">
        </div>

        {{-- Discount Amount --}}
        <div class="col-md-4">
            <label class="form-label">Discount Amount</label>
            <input type="number" name="discount_amount" id="discountAmount" class="form-control"
                value="{{ $discountAmount }}" readonly>
        </div>

        {{-- Payment Method --}}
        <div class="col-md-4">
            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="Full Payment" readonly required>
            <input type="hidden" name="payment_method" value="full">
        </div>
    </div>
</div>



    {{-- 8. Upload Death Certificate --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">8. Upload Death Certificate</div>
        <div class="card-body">
            <label class="form-label">Death Certificate (PDF, JPG, or PNG) <span class="text-danger">*</span></label>
            <input 
                type="file" 
                name="death_certificate_file" 
                class="form-control"
                accept="application/pdf,image/jpeg,image/png"
                @if(!($booking->detail && $booking->detail->death_certificate_path)) required @endif
                @if($disableNextForms) disabled @endif
            >
            @if($booking->detail && $booking->detail->death_certificate_path)
                <div class="mt-2">
                    <a href="{{ asset('storage/' . $booking->detail->death_certificate_path) }}" target="_blank" class="btn btn-link">
                        View Uploaded Certificate
                    </a>
                </div>
            @endif
            <div class="form-text">
                Maximum file size: 20 MB.
            </div>
        </div>
    </div>

    {{-- Submit Button --}}
    <div class="mt-4">
        <button type="submit" class="btn btn-primary btn-lg w-100" @if($disableNextForms) disabled @endif>
            <i class="bi bi-arrow-right-circle"></i>
            Submit
        </button>
    </div>
</form>

{{-- AGENT ASSISTANCE dynamic fields script --}}
 <script>

document.addEventListener('DOMContentLoaded', function () {
    // Payment discount logic
    var discountSelect = document.getElementById('discountEligibility');
    var discountDocumentDiv = document.getElementById('discountDocumentDiv');
    var totalAmountInput = document.getElementById('totalAmount');
    var discountAmountInput = document.getElementById('discountAmount');
    var totalAmountLabel = document.getElementById('totalAmountLabel');
    var finalAmountInput = document.getElementById('finalAmount');
    // Save original amount to prevent accumulation
    var originalTotal = parseFloat(totalAmountInput.value);

    function handleDiscountChange() {
        if (discountSelect.value == '1') {
            discountDocumentDiv.style.display = '';
            var discount = originalTotal * 0.20;
            var newTotal = originalTotal - discount;
            discountAmountInput.value = discount.toFixed(2);
            totalAmountInput.value = newTotal.toFixed(2);
            finalAmountInput.value = newTotal.toFixed(2);
        } else {
            discountDocumentDiv.style.display = 'none';
            discountAmountInput.value = '0.00';
            totalAmountInput.value = originalTotal.toFixed(2);
            finalAmountInput.value = originalTotal.toFixed(2);
            totalAmountLabel.innerHTML = 'Total Amount <span class="text-danger">*</span>';
        }
    }

    // Initial load
    handleDiscountChange();

    // Listen to changes
    discountSelect.addEventListener('change', handleDiscountChange);
});

document.addEventListener('DOMContentLoaded', function () {
    // AGENT ASSISTANCE LOGIC
    function updateAgentFields() {
        var needAgent = document.getElementById('needAgent').value;
        var agentTypeDiv = document.getElementById('agentTypeDiv');
        var agentType = document.getElementById('agentType').value;
        var clientAgentEmailDiv = document.getElementById('clientAgentEmailDiv');

        // Hide all by default
        agentTypeDiv.style.display = 'none';
        clientAgentEmailDiv.style.display = 'none';

        if (needAgent === 'yes') {
            agentTypeDiv.style.display = '';
            if (agentType === 'client') {
                clientAgentEmailDiv.style.display = '';
            }
        }
    }

    // Initial call on page load
    updateAgentFields();

    // Event listeners
    document.getElementById('needAgent').addEventListener('change', function() {
        // Reset agent type and email when switching to "No"
        if (this.value === 'no') {
            document.getElementById('agentType').selectedIndex = 0;
            document.getElementById('clientAgentEmailDiv').style.display = 'none';
        }
        updateAgentFields();
    });

    document.getElementById('agentType').addEventListener('change', updateAgentFields);
});
</script>

</x-agent-layout>
