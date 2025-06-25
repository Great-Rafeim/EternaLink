<x-layouts.funeral>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card bg-dark text-white border-0 shadow-lg rounded-3">
                    <div class="card-body">
                        <h2 class="mb-4">
                            Add Inventory Item
                        </h2>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('funeral.items.store') }}" id="itemForm" enctype="multipart/form-data">
                            @csrf

                            <div class="row g-4">
                                <!-- Item Name -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" required
                                           value="{{ old('name', '') }}"
                                           class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror">
                                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <!-- Item Image Upload -->
                                <div class="mb-3">
                                    <label class="form-label text-white">Item Image (optional)</label>
                                    <input type="file" name="image" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="previewImage(event)">
                                    <div class="mt-2" id="image-preview">
                                        <div class="text-muted">No image selected.</div>
                                        <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                                    </div>
                                </div>

                                <!-- Category -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Category <span class="text-danger">*</span></label>
                                    <select name="inventory_category_id" id="categorySelect"
                                            class="form-select bg-dark text-white border-secondary @error('inventory_category_id') is-invalid @enderror">
                                        <option value="">Select category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                data-is-asset="{{ $category->is_asset }}"
                                                {{ old('inventory_category_id', '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                                @if($category->is_asset)
                                                    [Asset: {{ ucfirst($category->reservation_mode) }}]
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('inventory_category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Brand -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Brand</label>
                                    <input type="text" name="brand"
                                           value="{{ old('brand', '') }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('brand') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Quantity (for consumables only) -->
                                <div class="col-md-3" id="quantityRow">
                                    <label class="form-label text-white">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" min="0"
                                           value="{{ old('quantity', 0) }}"
                                           class="form-control bg-dark text-white border-secondary @error('quantity') is-invalid @enderror">
                                    @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Low Stock Threshold (for consumables only) -->
                                <div class="col-md-3" id="lowStockRow">
                                    <label class="form-label text-white">Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" min="1"
                                           value="{{ old('low_stock_threshold', 5) }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('low_stock_threshold') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label text-white">Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select bg-dark text-white border-secondary @error('status') is-invalid @enderror">
                                        @foreach(['available', 'in_use', 'maintenance', 'reserved'] as $status)
                                            <option value="{{ $status }}"
                                                {{ old('status', 'available') == $status ? 'selected' : '' }}>
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
                                           value="{{ old('price', '') }}"
                                           class="form-control bg-dark text-white border-secondary">
                                    @error('price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label text-white">Selling Price</label>
                                    <input type="number" step="0.01" name="selling_price"
                                        value="{{ old('selling_price', '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('selling_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Expiry Date (for consumables only) -->
                                <div class="col-md-6" id="expiryRow">
                                    <label class="form-label text-white">Expiry Date (optional)</label>
                                    <input type="date" name="expiry_date"
                                        value="{{ old('expiry_date', '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('expiry_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- Shareable (for consumables only) -->
                                <div class="row">
                                <div class="col-md-6 d-flex align-items-center" id="shareableRow">
                                    <div class="form-check mt-3">
                                        <input type="checkbox" name="shareable" value="1" id="shareableSwitch"
                                            class="form-check-input"
                                            {{ old('shareable', false) ? 'checked' : '' }}>
                                        <label class="form-check-label text-white" for="shareableSwitch">
                                            Mark as shareable <span class="text-info">(resource sharing)</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Shareable Quantity -->
                                <div class="col-md-6" id="shareableQtyRow"
                                    style="{{ old('shareable', false) ? '' : 'display:none;' }}">
                                    <label for="shareable_quantity" class="form-label text-white">Shareable Quantity</label>
                                    <input type="number" min="1" class="form-control"
                                        id="shareable_quantity" name="shareable_quantity"
                                        value="{{ old('shareable_quantity', '') }}">
                                    <div class="form-text text-light">How many units from your stock can be shared with partners?</div>
                                </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    Add Item
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function previewImage(event) {
    let preview = document.getElementById('image-preview');
    preview.innerHTML = "";
    if (event.target.files && event.target.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let img = document.createElement('img');
            img.src = e.target.result;
            img.className = "img-thumbnail mb-2";
            img.style.maxHeight = "120px";
            preview.appendChild(img);

            // Add remove button
            let btn = document.createElement('button');
            btn.type = "button";
            btn.className = "btn btn-outline-danger btn-sm ms-2";
            btn.innerHTML = "Remove Image";
            btn.onclick = function() {
                document.querySelector('input[type=file][name=image]').value = "";
                preview.innerHTML = '<div class="text-muted">No image selected.</div>';
                document.getElementById('remove-image-input').value = "1";
            };
            preview.appendChild(btn);
        }
        reader.readAsDataURL(event.target.files[0]);
        document.getElementById('remove-image-input').value = "0";
    }
}
</script>


    <script>
        window.categoryAssetMap = @json($categories->pluck('is_asset', 'id'));
        document.addEventListener('DOMContentLoaded', function() {
            function toggleFieldsForCategory() {
                var select = document.getElementById('categorySelect');
                var selectedCategoryId = select.value;
                var isAsset = window.categoryAssetMap[selectedCategoryId] == 1;

                // For asset: hide quantity, threshold, expiry, shareable, shareable_qty
                document.getElementById('quantityRow').style.display = isAsset ? 'none' : '';
                document.getElementById('lowStockRow').style.display = isAsset ? 'none' : '';
                document.getElementById('expiryRow').style.display = isAsset ? 'none' : '';
                document.getElementById('shareableRow').style.display = isAsset ? 'none' : '';
                document.getElementById('shareableQtyRow').style.display = isAsset ? 'none' : '';

                // If asset, reset hidden fields
                if (isAsset) {
                    if(document.querySelector('input[name="quantity"]')) {
                        document.querySelector('input[name="quantity"]').value = 1;
                    }
                    if(document.querySelector('input[name="low_stock_threshold"]')) {
                        document.querySelector('input[name="low_stock_threshold"]').value = '';
                    }
                    if(document.querySelector('input[name="expiry_date"]')) {
                        document.querySelector('input[name="expiry_date"]').value = '';
                    }
                    if(document.querySelector('input[name="shareable"]')) {
                        document.querySelector('input[name="shareable"]').checked = false;
                    }
                    if(document.querySelector('input[name="shareable_quantity"]')) {
                        document.querySelector('input[name="shareable_quantity"]').value = '';
                    }
                }
            }

            function toggleShareableQty() {
                var shareableCheckbox = document.getElementById('shareableSwitch');
                var qtyGroup = document.getElementById('shareableQtyRow');
                qtyGroup.style.display =
                    shareableCheckbox && shareableCheckbox.checked && document.getElementById('shareableRow').style.display !== 'none'
                    ? ''
                    : 'none';
            }

            document.getElementById('categorySelect').addEventListener('change', function() {
                toggleFieldsForCategory();
                toggleShareableQty();
            });

            if(document.getElementById('shareableSwitch')) {
                document.getElementById('shareableSwitch').addEventListener('change', toggleShareableQty);
            }

            // Initial load
            toggleFieldsForCategory();
            toggleShareableQty();
        });
    </script>
</x-layouts.funeral>
