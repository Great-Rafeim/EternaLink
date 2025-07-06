<x-layouts.funeral>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4 py-4">
                        <h4 class="mb-0">
                            <i class="bi bi-send me-2"></i>
                            Resource Request Form
                        </h4>
                    </div>
                    <div class="card-body p-5">
                        <form method="POST" action="{{ route('funeral.partnerships.resource_requests.storeRequest') }}">
                            @csrf

                            @php
                                $isConsumable = $providerItem->category && !$providerItem->category->is_asset;
                                $requestedIsAsset = $requestedItem && $requestedItem->category && $requestedItem->category->is_asset;
                                $providerIsAsset = $providerItem->category && $providerItem->category->is_asset;
                            @endphp

                            {{-- ===================== FLEXIBLE CONSUMABLE LOGIC ===================== --}}
                            @if($isConsumable && !$requestedItem)
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        How do you want to add this item?
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="consumable_action" id="consumable_action_new" value="new" {{ old('consumable_action') == 'new' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="consumable_action_new">
                                            Add as a <strong>new item</strong> to my inventory
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="consumable_action" id="consumable_action_existing" value="existing" {{ old('consumable_action') == 'existing' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="consumable_action_existing">
                                            Add <strong>quantity to an existing item</strong>
                                        </label>
                                    </div>
                                </div>

                                <div id="newItemFields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">New Item Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="new_item_name" value="{{ old('new_item_name', $providerItem->name) }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Select Category <span class="text-danger">*</span></label>
                                        <select class="form-select" name="new_item_category_id">
                                            <option value="">-- Select --</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ old('new_item_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div id="existingItemFields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Search and Select Existing Item <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control mb-2" id="searchItemInput" placeholder="Search by name/category...">
                                        <select class="form-select" name="existing_item_id" id="existingItemSelect">
                                            <option value="">-- Select --</option>
                                            @foreach($userItems as $item)
                                                <option value="{{ $item->id }}" {{ old('existing_item_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }} @if($item->brand) ({{ $item->brand }}) @endif - Category: {{ $item->category->name ?? '-' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    function toggleFields() {
                                        let action = document.querySelector('input[name="consumable_action"]:checked');
                                        document.getElementById('newItemFields').style.display = (action && action.value === 'new') ? 'block' : 'none';
                                        document.getElementById('existingItemFields').style.display = (action && action.value === 'existing') ? 'block' : 'none';
                                    }
                                    let radios = document.querySelectorAll('input[name="consumable_action"]');
                                    radios.forEach(r => r.addEventListener('change', toggleFields));
                                    toggleFields();

                                    // Search filter for existing item select
                                    document.getElementById('searchItemInput').addEventListener('input', function() {
                                        const filter = this.value.toLowerCase();
                                        const options = document.querySelectorAll('#existingItemSelect option');
                                        options.forEach(option => {
                                            option.style.display = (option.value === "" || option.text.toLowerCase().includes(filter)) ? '' : 'none';
                                        });
                                    });

                                    // On submit, set hidden requested_item_id to existing_item_id if needed
                                    document.querySelector('form').addEventListener('submit', function() {
                                        let existingRadio = document.getElementById('consumable_action_existing');
                                        if(existingRadio && existingRadio.checked) {
                                            let selected = document.getElementById('existingItemSelect').value;
                                            document.querySelector('input[name="requested_item_id"]').value = selected;
                                        }
                                    });
                                });
                                </script>
                            @endif

                            {{-- ===================== ITEM SUMMARY ===================== --}}
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-semibold mb-1">You are requesting:</h6>
                                    <div class="mb-1">
                                        {{ $requestedItem->name ?? $providerItem->name }}
                                        <span class="badge bg-secondary">
                                            {{ ($requestedItem->category->name ?? $providerItem->category->name) ?? 'Uncategorized' }}
                                        </span>
                                        @if(($requestedItem->category->is_asset ?? $providerItem->category->is_asset) ?? false)
                                            <span class="badge bg-primary">Asset</span>
                                            <span class="badge bg-info text-dark">
                                                {{ ucfirst($requestedItem->category->reservation_mode ?? $providerItem->category->reservation_mode) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Consumable</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">From: {{ $providerItem->funeralUser->name ?? 'Unknown' }}</div>
                                    <div class="small text-muted">Brand: {{ $providerItem->brand ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                    <h6 class="fw-semibold mb-1">Provider's Shareable Stock:</h6>
                                    @if($providerIsAsset)
                                        <span class="fs-5 badge bg-success px-3 py-2">
                                            Available
                                        </span>
                                    @else
                                        <span class="fs-5 badge bg-success px-3 py-2">
                                            {{ $providerItem->shareable_quantity }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- ======================= FORM FIELDS ======================= --}}
                            <input type="hidden" name="requested_item_id" value="{{ $requestedItem->id ?? '' }}">
                            <input type="hidden" name="provider_item_id" value="{{ $providerItem->id }}">

                            <div class="row g-3">
                                {{-- ASSET: Reservation Inputs --}}
                                @if(($requestedItem && $requestedItem->category && $requestedItem->category->is_asset) || $providerIsAsset)
@php
    $today = now()->toDateString();
@endphp

<div class="col-md-6">
    <label for="reserved_start" class="form-label fw-semibold">
        Reservation Start <span class="text-danger">*</span>
    </label>
    <input type="date"
           name="reserved_start"
           id="reserved_start"
           class="form-control"
           value="{{ old('reserved_start') }}"
           min="{{ $today }}"
           required>
</div>
<div class="col-md-6">
    <label for="reserved_end" class="form-label fw-semibold">
        Reservation End <span class="text-danger">*</span>
    </label>
    <input type="date"
           name="reserved_end"
           id="reserved_end"
           class="form-control"
           value="{{ old('reserved_end') }}"
           min="{{ $today }}"
           required>
</div>

                                @else
                                    {{-- CONSUMABLE: Quantity + Fulfillment Date --}}
                                    <div class="col-md-4">
                                        <label for="quantity" class="form-label fw-semibold">
                                            Quantity Needed <span class="text-danger">*</span>
                                        </label>
                                        <input type="number"
                                               name="quantity"
                                               id="quantity"
                                               class="form-control"
                                               min="1"
                                               max="{{ $providerItem->shareable_quantity }}"
                                               value="1"
                                               required>
                                        <div id="quantityWarning" class="text-danger mt-2" style="display:none;">
                                            Quantity cannot be greater than the provider's shareable quantity ({{ $providerItem->shareable_quantity }}).
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="preferred_date" class="form-label fw-semibold">
                                            Preferred Fulfillment Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="preferred_date" id="preferred_date" class="form-control"
                                               value="{{ old('preferred_date') }}"
                                               min="{{ \Carbon\Carbon::now()->toDateString() }}"
                                               required>
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label for="purpose" class="form-label fw-semibold">
                                        Purpose/Reason <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="purpose" id="purpose" class="form-control" rows="2" required>{{ old('purpose') }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="delivery_method" class="form-label fw-semibold">
                                        Delivery/Transfer Method <span class="text-danger">*</span>
                                    </label>
                                    <select name="delivery_method" id="delivery_method" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="pickup">Pickup</option>
                                        <option value="delivery_by_partner">Delivery by Partner</option>
                                        <option value="courier">Courier/Logistics Service</option>
                                        <option value="not_applicable">Not Applicable</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label fw-semibold">
                                        Location for Pickup/Delivery <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" required>
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold">
                                        Additional Notes <span class="text-muted small">(Optional)</span>
                                    </label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_name" class="form-label fw-semibold">
                                        Contact Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="contact_name" id="contact_name" class="form-control" value="{{ old('contact_name', $user->name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_mobile" class="form-label fw-semibold">
                                        Contact Mobile <span class="text-muted small">(Optional)</span>
                                    </label>
                                    <input type="text" name="contact_mobile" id="contact_mobile" class="form-control" value="{{ old('contact_mobile') }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_email" class="form-label fw-semibold">
                                        Contact Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="contact_email" id="contact_email" class="form-control" value="{{ old('contact_email', $user->email) }}" required>
                                </div>
                            </div>

                            <div class="mt-5 text-end">
                                <button class="btn btn-primary btn-lg rounded-pill px-4">
                                    <i class="bi bi-send me-2"></i>
                                    Submit Request
                                </button>
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-lg rounded-pill ms-2 px-4">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@if(!(($requestedItem && $requestedItem->category && $requestedItem->category->is_asset) || $providerIsAsset))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const quantityInput = document.getElementById('quantity');
            const warning = document.getElementById('quantityWarning');
            const maxQty = parseInt(quantityInput.max);

            quantityInput.addEventListener('input', function() {
                if (parseInt(this.value) > maxQty) {
                    warning.style.display = 'block';
                    this.value = maxQty;
                } else {
                    warning.style.display = 'none';
                }
            });
        });
    </script>
@endif

</x-layouts.funeral>
