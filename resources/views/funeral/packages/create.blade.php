<x-layouts.funeral>
    <div class="container py-4">

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

        <h2 class="mb-4 text-white">Create Funeral Service Package</h2>
        <form action="{{ route('funeral.packages.store') }}" method="POST" id="package-form" enctype="multipart/form-data">
            @csrf

            {{-- Package Info --}}
            <div class="mb-3">
                <label class="form-label text-white">Package Name</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Package Image</label>
                <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(event)">
                <div class="mt-2" id="image-preview">
                    <div class="text-muted">No image selected.</div>
                    <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-white">Description</label>
                <textarea name="description" class="form-control">{{ old('description') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label text-white">Total Price</label>
                <input type="number" name="total_price" class="form-control bg-secondary text-white" readonly id="total-price" value="0.00">
            </div>

            {{-- Asset Categories --}}
            <div class="mb-3">
                <label class="form-label text-white">Bookable Asset Categories</label>
                <div id="asset-category-list">
                    @foreach($categories as $category)
                        @if($category->is_asset)
                            <div class="form-check mb-2 d-flex align-items-center gap-3">
                                <input class="form-check-input asset-category-checkbox" type="checkbox" 
                                    value="{{ $category->id }}" id="asset-category-{{ $category->id }}">
                                <label class="form-check-label text-white flex-grow-1" for="asset-category-{{ $category->id }}">
                                    {{ $category->name }} <span class="badge bg-info">Asset</span>
                                </label>
                                <input type="number" min="0" step="0.01"
                                    class="form-control form-control-sm asset-category-price-input"
                                    data-category-id="{{ $category->id }}"
                                    style="width:120px;display:none"
                                    placeholder="Asset Price">
                            </div>
                        @endif
                    @endforeach
                </div>
                {{-- Dynamic hidden inputs for submission --}}
                <div id="asset-hidden-fields"></div>
            </div>

            {{-- Consumable Categories --}}
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    Add Category and Items
                </button>
            </div>
            <div id="selected-categories"></div>

            <button type="submit" class="btn btn-success mt-4">Create Package</button>
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

    {{-- All Items by Category, hidden for JS use --}}
    <script>
        const itemsByCategory = @json($itemsByCategory);
        const categoriesList = @json($categories);
    </script>

    {{-- Main Script --}}
    <script>
        let selectedCategories = [];
        let selectedItems = {}; // { category_id: [{id, name, price, quantity}] }
        let selectedAssets = []; // [{category_id, price}]

        function recalculateTotal() {
            let total = 0;
            // Consumable item prices
            for (const catId in selectedItems) {
                selectedItems[catId].forEach(item => {
                    total += (item.price * item.quantity);
                });
            }
            // Asset category prices
            selectedAssets.forEach(asset => {
                let price = parseFloat(asset.price) || 0;
                total += price;
            });
            document.getElementById('total-price').value = total.toFixed(2);
        }

        // Asset Categories (dynamic add/remove/price fields)
        document.addEventListener('DOMContentLoaded', function() {
            // Handle check/uncheck asset category
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

            // Listen to changes in price fields
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
        });

        // Render asset category hidden fields for submission
        function updateAssetHiddenFields() {
            let html = '';
            selectedAssets.forEach((asset, i) => {
                html += `<input type="hidden" name="assets[${i}][category_id]" value="${asset.category_id}">`;
                html += `<input type="hidden" name="assets[${i}][price]" value="${asset.price}">`;
            });
            document.getElementById('asset-hidden-fields').innerHTML = html;
        }

        // ---- Consumable categories & items ----

        document.addEventListener('DOMContentLoaded', function() {
            // Add Category button click (modal)
            document.querySelectorAll('.select-category').forEach(li => {
                li.addEventListener('click', function() {
                    const catId = this.getAttribute('data-category-id');
                    const catName = this.getAttribute('data-category-name');
                    if (!selectedCategories.includes(catId)) {
                        selectedCategories.push(catId);
                        selectedItems[catId] = [];
                        renderCategories();
                    }
                    var categoryModal = bootstrap.Modal.getInstance(document.getElementById('categoryModal'));
                    categoryModal.hide();
                });
            });
        });

        function renderCategories() {
            let html = '';
            selectedCategories.forEach(catId => {
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
            selectedCategories = selectedCategories.filter(id => id !== catId);
            delete selectedItems[catId];
            renderCategories();
        }

        // Show Items Modal for a Category
        let currentCategory = null;
        function showItemsModal(catId) {
            currentCategory = catId;
            let items = itemsByCategory[catId] || [];
            let modalBody = '<div class="row">';
            items.forEach(item => {
                if (!selectedItems[catId].find(i => i.id == item.id)) {
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
            let itemsModal = new bootstrap.Modal(document.getElementById('itemsModal'));
            itemsModal.show();
        }

        // Add selected items from modal
        document.getElementById('add-selected-items-btn').addEventListener('click', function() {
            let checkboxes = document.querySelectorAll('.item-select-checkbox:checked');
            checkboxes.forEach(cb => {
                let itemId = cb.value;
                let itemName = cb.getAttribute('data-item-name');
                let itemPrice = parseFloat(cb.getAttribute('data-item-price'));
                selectedItems[currentCategory].push({ id: itemId, name: itemName, price: itemPrice, quantity: 1 });
            });
            let itemsModal = bootstrap.Modal.getInstance(document.getElementById('itemsModal'));
            itemsModal.hide();
            renderCategories();
        });

        function removeItem(catId, idx) {
            selectedItems[catId].splice(idx, 1);
            renderCategories();
        }

        function updateQuantity(catId, idx, qty) {
            qty = parseInt(qty) || 1;
            selectedItems[catId][idx].quantity = qty;
            recalculateTotal();
        }
    </script>

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
        }
        reader.readAsDataURL(event.target.files[0]);
        let removeInput = document.getElementById('remove-image-input');
        if (removeInput) removeInput.value = "0";
    }
}
function removeCurrentImage() {
    let preview = document.getElementById('image-preview');
    preview.innerHTML = '<div class="text-danger mb-2">Image will be removed after update.</div>' +
        '<input type="hidden" name="remove_image" id="remove-image-input" value="1">';
}
</script>

</x-layouts.funeral>
