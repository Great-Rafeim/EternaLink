<x-client-layout>



@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
        <br>
        <b>Next:</b> Please fill up the deceased’s personal details in the next form.
    </div>
@endif

<div class="card mb-4">
    
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

        <form action="{{ route('client.bookings.package_customization.send', $booking->id) }}" method="POST">
            @csrf
            <h6 class="fw-bold mb-2">Original Package</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Default Qty</th>
                            <th>Your Qty</th>
                            <th>Substitute Item</th>
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
                            $disabled = $isAsset ? 'disabled' : ''; // Remove status check
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
                            <td>
                                <input type="number" class="form-control-plaintext" readonly value="{{ $item->pivot->quantity }}">
                            </td>
                            <td style="width:120px;">
                                <input type="number"
                                    min="1"
                                    max="{{ $availableStock }}"
                                    class="form-control customization-input"
                                    name="custom_items[{{ $item->id }}][quantity]"
                                    value="{{ old("custom_items.{$item->id}.quantity", $inputQty) }}"
                                    data-default="{{ $item->pivot->quantity }}"
                                    {{ $disabled }}>
                            </td>
                            <td>
                                <select name="custom_items[{{ $item->id }}][substitute_for]"
                                        class="form-select customization-input"
                                        data-default="{{ $item->id }}"
                                        {{ $disabled }}>
                                    <option value="{{ $item->id }}" {{ $selectedItemId == $item->id ? 'selected' : '' }}>
                                        -- {{ $item->name }} (Default) --
                                    </option>
                                    @foreach($categoryItems as $alt)
                                        @if(
                                            !$isAsset &&
                                            $alt->id != $item->id &&
                                            !in_array($alt->id, $packageItemIds)
                                        )
                                            <option value="{{ $alt->id }}" {{ $selectedItemId == $alt->id ? 'selected' : '' }}>
                                                {{ $alt->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Display asset categories even if there are no specific items (for completeness) --}}
                    @foreach($assetCategories ?? [] as $assetCategory)
                        @if(!$booking->package->items->where('inventory_category_id', $assetCategory->id)->count())
                        <tr class="table-secondary">
                            <td>
                                {{ $assetCategory->name }}
                                <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                            </td>
                            <td colspan="6" class="text-muted">Included as a required asset (exact item to be assigned by parlor)</td>
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

        {{-- ... rest of your code ... --}}

        {{-- CUSTOMIZED PACKAGE TABLE (Only if exists) --}}
        @if(in_array($customized->status, ['pending', 'approved', 'denied']) && $customized->items()->count())
        <div class="mt-5">
            <h6 class="fw-bold mb-2">Your Customized Package</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
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
                            @endphp
                            <tr @if($isAsset) class="table-secondary" @endif>
                                <td>
                                    {{ $category }}
                                    @if($isAsset)
                                        <span class="badge bg-secondary ms-1" title="Bookable Asset">Asset</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $inventory->name }}
                                    @if($original)
                                        <br><small class="text-muted">(Substitute for: {{ $original->name }})</small>
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
                                    <td colspan="3" class="text-muted">
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
</script>


@php
    $disableNextForms = $customized->status === 'pending';
@endphp




        {{-- 2-7. Continue Booking Main Form --}}



 <form action="{{ route('client.bookings.continue.update', $booking->id) }}" method="POST" id="continueBookingForm" enctype="multipart/form-data">
    @csrf

    {{-- 2. Wake and Burial Schedule --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">2. Wake and Interment Schedule</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Wake Start Date <span class="text-danger">*</span></label>
                <input type="date" name="wake_start_date" class="form-control" value="{{ old('wake_start_date', $booking->detail->wake_start_date ?? '') }}"
                    @if($disableNextForms) disabled @else required @endif>
            </div>
            <div class="col-md-4">
                <label class="form-label">Wake End Date <span class="text-danger">*</span></label>
                <input type="date" name="wake_end_date" class="form-control" value="{{ old('wake_end_date', $booking->detail->wake_end_date ?? '') }}"
                    @if($disableNextForms) disabled @else required @endif>
            </div>
            <div class="col-md-4">
                <label class="form-label">Interment Date <span class="text-danger">*</span></label>
                <input type="date" name="interment_cremation_date" class="form-control" value="{{ old('interment_cremation_date', $booking->detail->interment_cremation_date ?? '') }}"
                    @if($disableNextForms) disabled @else required @endif>
            </div>
        </div>
    </div>

    {{-- 3. Cemetery/Plot --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">3. Cemetery Preferences</div>
        <div class="card-body row g-3">
            <div class="col-md-8">
                <label class="form-label">Preferred Cemetery/Crematory <span class="text-danger">*</span></label>
                <input type="text" name="cemetery_or_crematory" class="form-control" value="{{ old('cemetery_or_crematory', $booking->detail->cemetery_or_crematory ?? '') }}"
                    @if($disableNextForms) disabled @else required @endif>
            </div>
            <div class="col-md-4">
                <label class="form-label">Do you already have a plot reserved? <span class="text-danger">*</span></label>
                <select name="has_plot_reserved" class="form-select" @if($disableNextForms) disabled @else required @endif>
                    <option value="" {{ is_null(old('has_plot_reserved', $booking->detail->has_plot_reserved ?? null)) ? 'selected' : '' }}>--Select--</option>
                    <option value="1" {{ old('has_plot_reserved', $booking->detail->has_plot_reserved ?? null) == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ old('has_plot_reserved', $booking->detail->has_plot_reserved ?? null) === 0 ? 'selected' : '' }}>No</option>
                </select>
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
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Do you need an agent to assist you?</label>
                @php
                    $needAgentValue = old('need_agent');
                    if ($needAgentValue === null && isset($booking->detail->need_agent)) {
                        $needAgentValue = $booking->detail->need_agent == 1 ? 'yes' : ($booking->detail->need_agent == 0 ? 'no' : '');
                    }
                @endphp
                <select name="need_agent" class="form-select" id="needAgent" @if($disableNextForms) disabled @endif>
                    <option value="" {{ $needAgentValue === '' ? 'selected' : '' }}>--Select--</option>
                    <option value="yes" {{ $needAgentValue === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ $needAgentValue === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-4" id="agentTypeDiv">
                <label class="form-label">If yes, preferred agent:</label>
                @php
                    $agentTypeValue = old('agent_type');
                    if ($agentTypeValue === null && isset($booking->detail->agent_type)) {
                        $agentTypeValue = $booking->detail->agent_type;
                    }
                @endphp
                <select name="agent_type" class="form-select" id="agentType" @if($disableNextForms) disabled @endif>
                    <option value="" {{ $agentTypeValue === '' ? 'selected' : '' }}>--Select--</option>
                    <option value="parlor" {{ $agentTypeValue === 'parlor' ? 'selected' : '' }}>From Funeral Parlor</option>
                    <option value="client" {{ $agentTypeValue === 'client' ? 'selected' : '' }}>My Relative</option>
                </select>
            </div>
            <div class="col-md-4" id="clientAgentEmailDiv">
                <label class="form-label">Relative's Email</label>
                <input type="email" name="client_agent_email" class="form-control"
                       value="{{ old('client_agent_email', $booking->detail->client_agent_email ?? '') }}"
                       @if($disableNextForms) disabled @endif>
            </div>
        </div>
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

    {{-- 7. Payment --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">7. Payment Details</div>
        <div class="card-body row g-3">
            @php
                $hasCustom = $customized && $customized->status === 'approved';
                $defaultAmount = $booking->package->items->sum(function($item) {
                    return $item->pivot->quantity * ($item->selling_price ?? 0);
                });
                $amount = $hasCustom
                    ? ($customized->custom_total_price ?? $defaultAmount)
                    : $defaultAmount;
            @endphp

            <div class="col-md-4">
    <label class="form-label">Total Amount <span class="text-danger">*</span></label>
    <input type="number" name="amount" id="totalAmount" class="form-control"
        value="{{ old('amount', $totalAmount) }}" readonly required>
            </div>
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


 <script>
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
    </div>
</x-client-layout>