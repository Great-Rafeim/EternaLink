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

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-semibold mb-1">You are requesting:</h6>
                                <div class="mb-1">
                                    {{ $requestedItem->name }}
                                    <span class="badge bg-secondary">{{ $requestedItem->category->name ?? 'Uncategorized' }}</span>
                                </div>
                                <div class="small text-muted">From: {{ $providerItem->funeralUser->name ?? 'Unknown' }}</div>
                                <div class="small text-muted">Brand: {{ $providerItem->brand ?? '—' }}</div>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <h6 class="fw-semibold mb-1">Provider's Shareable Stock:</h6>
                                <span class="fs-5 badge bg-success px-3 py-2">{{ $providerItem->shareable_quantity }}</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('funeral.partnerships.resource_requests.storeRequest') }}">
                            @csrf
                            <input type="hidden" name="requested_item_id" value="{{ $requestedItem->id }}">
                            <input type="hidden" name="provider_item_id" value="{{ $providerItem->id }}">

                            <div class="row g-3">
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
                                    <label for="purpose" class="form-label fw-semibold">
                                        Purpose/Reason <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="purpose" id="purpose" class="form-control" rows="2" required>{{ old('purpose') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="preferred_date" class="form-label fw-semibold">
                                        Preferred Fulfillment Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="preferred_date" id="preferred_date" class="form-control"
                                        value="{{ old('preferred_date') }}" required>
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
                                    </select>
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
                                <div class="col-12">
                                    <label for="location" class="form-label fw-semibold">
                                        Location for Pickup/Delivery <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" required>
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

</x-layouts.funeral>
