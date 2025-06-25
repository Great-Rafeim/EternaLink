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
                              action="{{ isset($item) ? route('funeral.items.update', $item) : route('funeral.items.store') }}" enctype="multipart/form-data">
                            @csrf
                            @if(isset($item)) @method('PUT') @endif

                            <div class="mb-3">
                                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}"
                                       class="form-control bg-secondary border-0 text-white @error('name') is-invalid @enderror" required>
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <!-- Item Image Upload -->
                            <div class="mb-3">
                                <label class="form-label text-white">Item Image (optional)</label>
                                <input type="file" name="image" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="previewImage(event)">
                                <div class="mt-2" id="image-preview">
                                    @if(old('remove_image') == "1")
                                        <div class="text-muted">No image selected.</div>
                                    @elseif(isset($item) && $item->image && !old('image'))
                                        <img src="{{ asset('storage/' . $item->image) }}" alt="Current Image" class="img-thumbnail mb-2" style="max-height:120px;">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCurrentImage()">Remove Image</button>
                                    @else
                                        <div class="text-muted">No image selected.</div>
                                    @endif
                                    <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                                </div>
                                @error('image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="inventory_category_id" id="categorySelect"
                                        class="form-select bg-secondary border-0 text-white @error('inventory_category_id') is-invalid @enderror">
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            data-is-asset="{{ $category->is_asset }}"
                                            {{ old('inventory_category_id', $item->inventory_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                            @if($category->is_asset)
                                                [Asset: {{ ucfirst($category->reservation_mode) }}]
                                            @endif
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
                                <div class="col-md-6 mb-3" id="quantityRow">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" min="0" value="{{ old('quantity', $item->quantity ?? 0) }}"
                                           class="form-control bg-secondary border-0 text-white @error('quantity') is-invalid @enderror">
                                    @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select bg-secondary border-0 text-white @error('status') is-invalid @enderror">
                                        @foreach(['available', 'in_use', 'maintenance', 'reserved'] as $status)
                                            <option value="{{ $status }}"
                                                {{ old('status', $item->status ?? '') == $status ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3" id="lowStockRow">
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
                                <div class="col-md-6 mb-3" id="expiryRow">
                                    <label class="form-label text-white">Expiry Date (optional)</label>
                                    <input type="date" name="expiry_date"
                                        value="{{ old('expiry_date', $item->expiry_date ?? '') }}"
                                        class="form-control bg-dark text-white border-secondary">
                                    @error('expiry_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="row align-items-center mb-3">
                                <!-- Shareable Toggle (Always show for asset/consumable) -->
                                <div class="col-md-6" id="shareableRow">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input bg-secondary border-0" type="checkbox" name="shareable" value="1"
                                            id="shareableSwitch"
                                            {{ old('shareable', $item->shareable ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label text-white" for="shareableSwitch">Mark as shareable</label>
                                    </div>
                                    <div class="form-text text-info">
                                        <span id="shareableHint"></span>
                                    </div>
                                </div>
                                <!-- Shareable Quantity (only for consumables, hidden for assets) -->
                                <div class="col-md-6" id="shareableQtyRow"
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
        window.categoryAssetMap = @json($categories->pluck('is_asset', 'id'));
        window.categoryReservationModeMap = @json($categories->pluck('reservation_mode', 'id'));

        function toggleFieldsForCategory() {
            var select = document.getElementById('categorySelect');
            var selectedCategoryId = select.value;
            var isAsset = window.categoryAssetMap[selectedCategoryId] == 1;

            // Toggle fields
            document.getElementById('quantityRow').style.display = isAsset ? 'none' : '';
            document.getElementById('lowStockRow').style.display = isAsset ? 'none' : '';
            document.getElementById('expiryRow').style.display = isAsset ? 'none' : '';
            document.getElementById('shareableQtyRow').style.display = !isAsset && document.getElementById('shareableSwitch').checked ? '' : 'none';

            // Show shareable toggle always
            document.getElementById('shareableRow').style.display = '';

            // Hint text for assets
            var hint = '';
            if (isAsset) {
                hint = "Bookable asset: If marked as shareable, this asset can be reserved by partner parlors.";
            } else {
                hint = "Consumable: Shareable means other parlors can request some of your stock.";
            }
            document.getElementById('shareableHint').innerText = hint;

            // Reset hidden fields for assets
            if (isAsset) {
                if(document.querySelector('input[name=\"quantity\"]')) {
                    document.querySelector('input[name=\"quantity\"]').value = 1;
                }
                if(document.querySelector('input[name=\"low_stock_threshold\"]')) {
                    document.querySelector('input[name=\"low_stock_threshold\"]').value = '';
                }
                if(document.querySelector('input[name=\"expiry_date\"]')) {
                    document.querySelector('input[name=\"expiry_date\"]').value = '';
                }
                if(document.querySelector('input[name=\"shareable_quantity\"]')) {
                    document.querySelector('input[name=\"shareable_quantity\"]').value = '';
                }
            }
        }

        function toggleShareableQty() {
            var select = document.getElementById('categorySelect');
            var selectedCategoryId = select.value;
            var isAsset = window.categoryAssetMap[selectedCategoryId] == 1;

            var shareableCheckbox = document.getElementById('shareableSwitch');
            var qtyGroup = document.getElementById('shareableQtyRow');
            // Show only for consumables and if shareable is checked
            qtyGroup.style.display = !isAsset && shareableCheckbox && shareableCheckbox.checked ? '' : 'none';
        }

        document.getElementById('categorySelect').addEventListener('change', function() {
            toggleFieldsForCategory();
            toggleShareableQty();
        });

        if(document.getElementById('shareableSwitch')) {
            document.getElementById('shareableSwitch').addEventListener('change', toggleShareableQty);
        }

        window.addEventListener('DOMContentLoaded', function() {
            toggleFieldsForCategory();
            toggleShareableQty();
        });
    </script>


<script>
        // --- IMAGE PREVIEW + REMOVE LOGIC ---
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

        // Remove image if "Remove Image" button (prefilled) is clicked
        function removeCurrentImage() {
            let preview = document.getElementById('image-preview');
            preview.innerHTML = '<div class="text-muted">No image selected.</div>';
            document.querySelector('input[type="file"][name="image"]').value = "";
            document.getElementById('remove-image-input').value = "1";
        }
</script>
</x-layouts.funeral>
