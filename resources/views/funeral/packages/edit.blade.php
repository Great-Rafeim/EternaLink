<x-layouts.funeral>
    <div class="container py-4">
        <h2 class="mb-4 text-white">Edit Funeral Service Package</h2>
        <form action="{{ route('funeral.packages.update', $package->id) }}" method="POST" id="package-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label text-white">Package Name</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name', $package->name) }}">
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Package Image</label>
                <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(event)">
                <div class="mt-2" id="image-preview">
                    @if($package->image)
                        <img src="{{ asset('storage/'.$package->image) }}" alt="Current Image" class="img-thumbnail mb-2" style="max-height:120px;">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCurrentImage()">Remove Image</button>
                    @endif
                    <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Description</label>
                <textarea name="description" class="form-control">{{ old('description', $package->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label text-white">Total Price</label>
                <input type="number" name="total_price" class="form-control bg-secondary text-white" readonly id="total-price" value="0.00">
            </div>

{{-- Bookable Asset Categories --}}
<div class="mb-3">
    <label class="form-label text-white">Bookable Asset Categories</label>
    <div id="asset-category-list">
        @php
            // Build asset price lookup and selection
            $oldAssets = old('assets') ?? null;
            $initialAssets = [];
            if ($oldAssets) {
                foreach ($oldAssets as $a) {
                    $initialAssets[$a['category_id']] = $a['price'];
                }
            } else {
                foreach($package->assetCategories as $ac) {
                    $initialAssets[$ac->inventory_category_id] = $ac->price;
                }
            }
        @endphp
        @foreach($categories as $category)
            @if($category->is_asset)
                <div class="form-check mb-2 d-flex align-items-center gap-3">
                    <input class="form-check-input asset-category-checkbox"
                        type="checkbox"
                        value="{{ $category->id }}"
                        id="asset-category-{{ $category->id }}"
                        {{ array_key_exists($category->id, $initialAssets) ? 'checked' : '' }}>
                    <label class="form-check-label text-white flex-grow-1" for="asset-category-{{ $category->id }}">
                        {{ $category->name }} <span class="badge bg-info">Asset</span>
                    </label>
                    <input type="number" min="0" step="0.01"
                        class="form-control form-control-sm asset-category-price-input"
                        data-category-id="{{ $category->id }}"
                        style="width:120px;{{ array_key_exists($category->id, $initialAssets) ? '' : 'display:none' }}"
                        placeholder="Asset Price"
                        value="{{ old('assets.' . $loop->index . '.price', $initialAssets[$category->id] ?? '') }}">
                </div>
            @endif
        @endforeach
    </div>
    <div id="asset-hidden-fields"></div>
</div>


            {{-- Consumable Categories --}}
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    Add Category and Items
                </button>
            </div>
            <div id="selected-categories"></div>

            <button type="submit" class="btn btn-success mt-4">Update Package</button>
        </form>
    </div>

    {{-- Category Modal --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Select a Consumable Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        @foreach($categories as $category)
                            @if(!$category->is_asset)
                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center select-category"
                                    data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}">
                                    {{ $category->name }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Items Modal --}}
    <div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemsModalLabel">Select Items</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="items-modal-body">
                    {{-- JS will fill this --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="add-selected-items-btn">Add Selected Items</button>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript --}}
    <script>
        const itemsByCategory = @json($itemsByCategory);
        const categoriesList = @json($categories);

        // Initial data for asset categories
        let selectedAssets = [];
        @foreach($categories as $category)
            @if($category->is_asset && array_key_exists($category->id, $initialAssets))
                selectedAssets.push({
                    category_id: "{{ $category->id }}",
                    price: "{{ $initialAssets[$category->id] }}"
                });
            @endif
        @endforeach

        let selectedCategories = @json(array_map('strval', array_keys($currentSelection)));
        let selectedItems = @json($currentSelection);

        function recalculateTotal() {
            let total = 0;
            // Consumables
            for (const catId in selectedItems) {
                selectedItems[catId].forEach(item => {
                    total += (item.price * item.quantity);
                });
            }
            // Asset categories
            selectedAssets.forEach(asset => {
                let price = parseFloat(asset.price) || 0;
                total += price;
            });
            document.getElementById('total-price').value = total.toFixed(2);
        }

        // Asset Categories logic
        document.addEventListener('DOMContentLoaded', function() {
            // Check/uncheck asset category
            document.querySelectorAll('.asset-category-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const catId = this.value;
                    const priceInput = document.querySelector('.asset-category-price-input[data-category-id="'+catId+'"]');
                    if (this.checked) {
                        priceInput.style.display = "inline-block";
                        // Add to selectedAssets if not exists
                        if (!selectedAssets.find(a => a.category_id == catId)) {
                            selectedAssets.push({category_id: catId, price: priceInput.value || 0});
                        }
                    } else {
                        priceInput.style.display = "none";
                        priceInput.value = "";
                        selectedAssets = selectedAssets.filter(a => a.category_id != catId);
                    }
                    updateAssetHiddenFields();
                    recalculateTotal();
                });
            });
            // Price field changes
            document.querySelectorAll('.asset-category-price-input').forEach(inp => {
                inp.addEventListener('input', function() {
                    const catId = this.getAttribute('data-category-id');
                    let asset = selectedAssets.find(a => a.category_id == catId);
                    if (asset) {
                        asset.price = this.value;
                    }
                    updateAssetHiddenFields();
                    recalculateTotal();
                });
            });
            // On load, update hidden fields and recalc total
            updateAssetHiddenFields();
            renderCategories();
            recalculateTotal();
        });

        // Render hidden fields for submission
        function updateAssetHiddenFields() {
            let html = '';
            selectedAssets.forEach((asset, i) => {
                html += `<input type="hidden" name="assets[${i}][category_id]" value="${asset.category_id}">`;
                html += `<input type="hidden" name="assets[${i}][price]" value="${asset.price}">`;
            });
            document.getElementById('asset-hidden-fields').innerHTML = html;
        }

        // Consumable categories and items
        function renderCategories() {
            let html = '';
            selectedCategories.forEach(catId => {
                catId = catId.toString();
                html += `
                    <div class="card mb-3" id="category-card-${catId}">
                        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                            <span>${getCategoryName(catId)}</span>
                            <div>
                                <button type="button" class="btn btn-light btn-sm me-2" onclick="showItemsModal('${catId}')">Add item(s)</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeCategory('${catId}')">Remove Category</button>
                            </div>
                        </div>
                        <div class="card-body" id="category-items-${catId}">
                            ${renderItems(catId)}
                        </div>
                    </div>
                `;
            });
            document.getElementById('selected-categories').innerHTML = html;
            recalculateTotal();
        }

        function renderItems(catId) {
            catId = catId.toString();
            if (!selectedItems[catId] || selectedItems[catId].length === 0) {
                return `<div class="text-secondary">No items added yet.</div>`;
            }
            let html = '<ul class="list-group">';
            selectedItems[catId].forEach((item, idx) => {
                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-dark text-white">
                        <div>
                            <input type="hidden" name="items[${catId}][${idx}][id]" value="${item.id}">
                            ${item.name}
                            <span class="badge bg-info ms-2">₱${item.price.toFixed(2)}</span>
                        </div>
                        <div>
                            <input type="number" min="1" class="form-control d-inline-block" style="width:80px"
                                name="items[${catId}][${idx}][quantity]" value="${item.quantity}" 
                                onchange="updateQuantity('${catId}', ${idx}, this.value)">
                            <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="removeItem('${catId}', ${idx})">Remove</button>
                        </div>
                    </li>
                `;
            });
            html += '</ul>';
            return html;
        }

        function getCategoryName(catId) {
            let cat = categoriesList.find(c => c.id == catId);
            return cat ? cat.name : 'Unknown';
        }

        function removeCategory(catId) {
            catId = catId.toString();
            selectedCategories = selectedCategories.filter(id => id !== catId);
            delete selectedItems[catId];
            renderCategories();
        }

        let currentCategory = null;
        function showItemsModal(catId) {
            currentCategory = catId.toString();
            let items = itemsByCategory[currentCategory] || [];
            let modalBody = '<div class="row">';
            items.forEach(item => {
                if (!selectedItems[currentCategory].find(i => i.id == item.id)) {
                    modalBody += `
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input item-select-checkbox" type="checkbox" 
                                    value="${item.id}" data-item-name="${item.name}" data-item-price="${item.price}" id="item-check-${item.id}">
                                <label class="form-check-label" for="item-check-${item.id}">${item.name}</label>
                            </div>
                        </div>
                    `;
                }
            });
            modalBody += '</div>';
            if (items.length === 0) {
                modalBody = '<div class="text-secondary">No items available in this category.</div>';
            }
            document.getElementById('items-modal-body').innerHTML = modalBody;
            const itemsModal = new bootstrap.Modal(document.getElementById('itemsModal'));
            itemsModal.show();
        }

        document.getElementById('add-selected-items-btn').addEventListener('click', function() {
            let checkboxes = document.querySelectorAll('.item-select-checkbox:checked');
            checkboxes.forEach(cb => {
                let itemId = cb.value;
                let itemName = cb.getAttribute('data-item-name');
                let itemPrice = parseFloat(cb.getAttribute('data-item-price'));
                selectedItems[currentCategory].push({ id: itemId, name: itemName, price: itemPrice, quantity: 1 });
            });
            const itemsModal = bootstrap.Modal.getInstance(document.getElementById('itemsModal'));
            itemsModal.hide();
            renderCategories();
        });

        function removeItem(catId, idx) {
            catId = catId.toString();
            selectedItems[catId].splice(idx, 1);
            renderCategories();
        }

        function updateQuantity(catId, idx, qty) {
            catId = catId.toString();
            qty = parseInt(qty) || 1;
            selectedItems[catId][idx].quantity = qty;
            recalculateTotal();
        }

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
                }
                reader.readAsDataURL(event.target.files[0]);
                document.getElementById('remove-image-input').value = "0";
            }
        }

        function removeCurrentImage() {
            let preview = document.getElementById('image-preview');
            preview.innerHTML = '<div class="text-danger mb-2">Image will be removed after update.</div>' +
                '<input type="hidden" name="remove_image" id="remove-image-input" value="1">';
        }
    </script>
</x-layouts.funeral>
