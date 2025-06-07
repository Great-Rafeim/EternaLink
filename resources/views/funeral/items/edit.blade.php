<x-layouts.funeral>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-dark text-white shadow-lg border-0 rounded-3">
                    <div class="card-body">
                        <h2 class="mb-4">
                            <i class="bi {{ isset($item) ? 'bi-pencil-square' : 'bi-plus-circle' }}"></i>
                            {{ isset($item) ? 'Edit' : 'Add' }} Inventory Item
                        </h2>
                        <form method="POST"
                              action="{{ isset($item) ? route('funeral.items.update', $item) : route('funeral.items.store') }}">
                            @csrf
                            @if(isset($item)) @method('PUT') @endif

                            <div class="mb-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}"
                                       class="form-control bg-secondary border-0 text-white" required>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="inventory_category_id" class="form-select bg-secondary border-0 text-white">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('inventory_category_id', $item->inventory_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('inventory_category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" value="{{ old('brand', $item->brand ?? '') }}"
                                       class="form-control bg-secondary border-0 text-white">
                                @error('brand') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" min="0" value="{{ old('quantity', $item->quantity ?? 0) }}"
                                           class="form-control bg-secondary border-0 text-white">
                                    @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select bg-secondary border-0 text-white">
                                        @foreach(['available', 'in_use', 'maintenance'] as $status)
                                            <option value="{{ $status }}"
                                                {{ old('status', $item->status ?? '') == $status ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" min="1" value="{{ old('low_stock_threshold', $item->low_stock_threshold ?? 5) }}"
                                    class="form-control bg-secondary border-0 text-white">
                                @error('low_stock_threshold') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" step="0.01" name="price" value="{{ old('price', $item->price ?? '') }}"
                                       class="form-control bg-secondary border-0 text-white">
                                @error('price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white">Selling Price</label>
                                    <input type="number" step="0.01" name="selling_price"
                                        value="{{ old('selling_price', $item->selling_price ?? '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('selling_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white">Expiry Date (optional)</label>
                                    <input type="date" name="expiry_date"
                                        value="{{ old('expiry_date', $item->expiry_date ?? '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('expiry_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row align-items-center mb-3">
                                <!-- Shareable Toggle -->
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input bg-secondary border-0" type="checkbox" name="shareable" value="1"
                                            id="shareableSwitch"
                                            {{ old('shareable', $item->shareable ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label text-white" for="shareableSwitch">Mark as shareable</label>
                                    </div>
                                </div>

                                <!-- Shareable Quantity (show if checked or previously marked as shareable) -->
                                <div class="col-md-6" id="shareableQtyGroup"
                                    style="{{ old('shareable', $item->shareable ?? false) ? '' : 'display:none;' }}">
                                    <label for="shareable_quantity" class="form-label text-white">Shareable Quantity</label>
                                    <input type="number" min="1" class="form-control"
                                        id="shareable_quantity" name="shareable_quantity"
                                        value="{{ old('shareable_quantity', $item->shareable_quantity ?? '') }}">
                                    <div class="form-text text-light">How many units from your stock can be shared with partners?</div>
                                </div>
                            </div>


                            <div class="d-flex justify-content-end">
                                <a href="{{ route('funeral.items.index') }}" class="btn btn-outline-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi {{ isset($item) ? 'bi-save' : 'bi-plus-circle' }}"></i>
                                    {{ isset($item) ? 'Update Item' : 'Add Item' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    function toggleShareableQty() {
        var shareableCheckbox = document.getElementById('shareableSwitch');
        var qtyGroup = document.getElementById('shareableQtyGroup');
        qtyGroup.style.display = shareableCheckbox.checked ? 'block' : 'none';
    }

    document.getElementById('shareableSwitch').addEventListener('change', toggleShareableQty);
    window.addEventListener('DOMContentLoaded', function() {
        toggleShareableQty(); // This will respect the initial state!
    });
</script>

</x-layouts.funeral>
