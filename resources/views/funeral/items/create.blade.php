<x-layouts.funeral>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card bg-dark text-white border-0 shadow-lg rounded-3">
                    <div class="card-body">
                        <h2 class="mb-4">
                            {{ isset($inventoryItem) ? 'Edit Inventory Item' : 'Add Inventory Item' }}
                        </h2>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST"
                              action="{{ isset($inventoryItem) ? route('funeral.items.update', $inventoryItem) : route('funeral.items.store') }}">
                            @csrf
                            @if(isset($inventoryItem)) @method('PUT') @endif

                            <div class="row g-4">
                                <!-- Item Name -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Item Name</label>
                                    <input type="text" name="name" required
                                           value="{{ old('name', $inventoryItem->name ?? '') }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Category -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Category</label>
                                    <select name="inventory_category_id" class="form-select bg-dark text-white border-secondary">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('inventory_category_id', $inventoryItem->inventory_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('inventory_category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Brand -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Brand</label>
                                    <input type="text" name="brand"
                                           value="{{ old('brand', $inventoryItem->brand ?? '') }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('brand') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Quantity -->
                                <div class="col-md-3">
                                    <label class="form-label text-white">Quantity</label>
                                    <input type="number" name="quantity" min="0"
                                           value="{{ old('quantity', $inventoryItem->quantity ?? 0) }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Low Stock Threshold -->
                                <div class="col-md-3">
                                    <label class="form-label text-white">Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" min="1"
                                           value="{{ old('low_stock_threshold', $inventoryItem->low_stock_threshold ?? 5) }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('low_stock_threshold') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Status</label>
                                    <select name="status" class="form-select bg-dark text-white border-secondary">
                                        @foreach(['available', 'in_use', 'maintenance'] as $status)
                                            <option value="{{ $status }}"
                                                {{ old('status', $inventoryItem->status ?? '') == $status ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Price -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Price (optional)</label>
                                    <input type="number" step="0.01" name="price"
                                           value="{{ old('price', $inventoryItem->price ?? '') }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label text-white">Selling Price</label>
                                    <input type="number" step="0.01" name="selling_price"
                                        value="{{ old('selling_price', $inventoryItem->selling_price ?? '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('selling_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label text-white">Expiry Date (optional)</label>
                                    <input type="date" name="expiry_date"
                                        value="{{ old('expiry_date', isset($inventoryItem) ? $inventoryItem->expiry_date : '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('expiry_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="row">
                                <!-- Shareable -->
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check mt-3">
                                        <input type="checkbox" name="shareable" value="1" id="shareable"
                                            class="form-check-input"
                                            {{ old('shareable', $inventoryItem->shareable ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label text-white" for="shareable">
                                            Mark as shareable
                                        </label>
                                    </div>
                                </div>

                                <!-- Shareable Quantity (hidden by default) -->
                                <div class="col-md-6" id="shareableQtyGroup" style="display:none;">
                                    <label for="shareable_quantity" class="form-label text-white">Shareable Quantity</label>
                                    <input type="number" min="1" class="form-control"
                                        id="shareable_quantity" name="shareable_quantity"
                                        value="{{ old('shareable_quantity', $inventoryItem->shareable_quantity ?? '') }}">
                                    <div class="form-text text-light">How many units from your stock can be shared with partners?</div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    {{ isset($inventoryItem) ? 'Update Item' : 'Add Item' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.funeral>
