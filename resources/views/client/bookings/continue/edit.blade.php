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

        {{-- Get all package item IDs to prevent substituting with any item already in the package --}}
        @php
            $packageItemIds = $booking->package->items->pluck('id')->toArray();
        @endphp


        <form action="{{ route('client.bookings.package_customization.send', $booking->id) }}" method="POST">
            @csrf
            <h6 class="fw-bold mb-2">Customize Package</h6>
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
                            $disabled = ($isAsset || $customized->status === 'pending') ? 'disabled' : '';
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
                        @if(! $booking->package->items->where('inventory_category_id', $assetCategory->id)->count())
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
                <button type="submit" id="sendCustomizationBtn" class="btn btn-success"
                    {{ $customized->status === 'pending' ? 'disabled' : '' }}>
                    Send Customization Request
                </button>
            </div>
        </form>

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
                    // Get asset category IDs from customized items
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
    const customizationInputs = document.querySelectorAll('.customization-input:not([disabled])');
    const sendBtn = document.getElementById('sendCustomizationBtn');
    const originalValues = {};
    customizationInputs.forEach(input => {
        originalValues[input.name] = input.value;
    });

    function customizationChanged() {
        return Array.from(customizationInputs).some(input => input.value !== originalValues[input.name]);
    }

    function updateFormState() {
        if (sendBtn) sendBtn.disabled = !customizationChanged();
    }

    customizationInputs.forEach(input => {
        input.addEventListener('input', updateFormState);
    });

    updateFormState();

    @if($customized->status === 'pending')
        customizationInputs.forEach(el => el.disabled = true);
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
                        <label class="form-label">Wake Start Date</label>
                        <input type="date" name="wake_start_date" class="form-control" value="{{ old('wake_start_date', $booking->detail->wake_start_date ?? '') }}"
                            @if($disableNextForms) disabled @endif>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Wake End Date</label>
                        <input type="date" name="wake_end_date" class="form-control" value="{{ old('wake_end_date', $booking->detail->wake_end_date ?? '') }}"
                            @if($disableNextForms) disabled @endif>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interment Date</label>
                        <input type="date" name="burial_date" class="form-control" value="{{ old('burial_date', $booking->detail->burial_date ?? '') }}"
                            @if($disableNextForms) disabled @endif>
                    </div>
                </div>
            </div>

            {{-- 3. Cemetery/Plot --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">3. Cemetery Preferences</div>
                <div class="card-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Preferred Cemetery/Crematory</label>
                        <input type="text" name="cemetery_or_crematory" class="form-control" value="{{ old('cemetery_or_crematory', $booking->detail->cemetery_or_crematory ?? '') }}"
                            @if($disableNextForms) disabled @endif>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Do you already have a plot reserved?</label>
                        <select name="has_plot_reserved" class="form-select" @if($disableNextForms) disabled @endif>
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
                    <input type="text" name="attire" class="form-control" value="{{ old('attire', $booking->detail->attire ?? '') }}"
                        @if($disableNextForms) disabled @endif>
                </div>
            </div>

    {{-- 5. Agent Assistance --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">5. Agent Assistance</div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Do you need an agent to assist you?</label>
                <select name="need_agent" class="form-select" id="needAgent" @if($disableNextForms) disabled @endif>
                    <option value="" {{ old('need_agent', $booking->detail->need_agent ?? '') == '' ? 'selected' : '' }}>--Select--</option>
                    <option value="yes" {{ old('need_agent', $booking->detail->need_agent ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ old('need_agent', $booking->detail->need_agent ?? '') == 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-4" id="agentTypeDiv">
                <label class="form-label">If yes, preferred agent:</label>
                <select name="agent_type" class="form-select" id="agentType" @if($disableNextForms) disabled @endif>
                    <option value="" {{ old('agent_type', $booking->detail->agent_type ?? '') == '' ? 'selected' : '' }}>--Select--</option>
                    <option value="parlor" {{ old('agent_type', $booking->detail->agent_type ?? '') == 'parlor' ? 'selected' : '' }}>From Funeral Parlor</option>
                    <option value="client" {{ old('agent_type', $booking->detail->agent_type ?? '') == 'client' ? 'selected' : '' }}>My Relative</option>
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
                    <input type="text" name="post_services" class="form-control" value="{{ old('post_services', $booking->detail->post_services ?? '') }}"
                        placeholder="e.g., after-care, memorial mass, etc." @if($disableNextForms) disabled @endif>
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
                <label class="form-label">Total Amount</label>
                <input type="number" name="amount" id="totalAmount" class="form-control"
                    value="{{ old('amount', $amount) }}" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">Payment Method</label>
                <input type="text" class="form-control" value="Full Payment" readonly>
                <input type="hidden" name="payment_method" value="full">
            </div>
        </div>
    </div>



    
    {{-- 8. Upload Death Certificate --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">8. Upload Death Certificate</div>
        <div class="card-body">
            <label class="form-label">Death Certificate (PDF, JPG, or PNG)</label>
            <input type="file" name="death_certificate_file" class="form-control"
                   accept="application/pdf,image/jpeg,image/png" required>
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