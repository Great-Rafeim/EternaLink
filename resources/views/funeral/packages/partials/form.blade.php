<div class="mb-3">
    <label class="form-label">Package Name</label>
    <input type="text" name="name" class="form-control"
           value="{{ old('name', $package->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control">{{ old('description', $package->description ?? '') }}</textarea>
</div>

<div id="categoriesContainer">
    <h4>Categories</h4>
    <button type="button" class="btn btn-secondary mb-3" onclick="addCategory()">Add Category</button>

    {{-- Prepopulate categories and items if editing --}}
    @if(isset($package) && $package->categories)
        @foreach(old('categories', $package->categories->toArray()) as $index => $category)
            <div class="category border rounded p-3 mb-3" data-category-index="{{ $index }}">
                <div class="d-flex justify-content-between mb-2">
                    <input type="text" name="categories[{{ $index }}][name]" class="form-control category-name" placeholder="Category Name" value="{{ $category['name'] ?? '' }}" required>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeCategory(this)">Remove Category</button>
                </div>
                <div class="itemsContainer">
                    <h5>Items</h5>
                    <button type="button" class="btn btn-info btn-sm mb-2" onclick="addItem(this)">Add Item</button>
                    <div class="itemsList">
                        @if(!empty($category['items']))
                            @foreach($category['items'] as $itemIndex => $item)
                                <div class="item d-flex gap-2 mb-2 align-items-start" data-item-index="{{ $itemIndex }}">
                                    <input type="text" name="categories[{{ $index }}][items][{{ $itemIndex }}][name]" class="form-control item-name" placeholder="Item Name" value="{{ $item['name'] ?? '' }}" required>
                                    <input type="number" name="categories[{{ $index }}][items][{{ $itemIndex }}][quantity]" class="form-control item-quantity" placeholder="Qty" min="1" value="{{ $item['quantity'] ?? 1 }}" required>
                                    <input type="number" name="categories[{{ $index }}][items][{{ $itemIndex }}][price]" class="form-control item-price" placeholder="Price" step="0.01" min="0" value="{{ $item['price'] ?? 0 }}">
                                    <input type="text" name="categories[{{ $index }}][items][{{ $itemIndex }}][description]" class="form-control item-description" placeholder="Description (optional)" value="{{ $item['description'] ?? '' }}">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">X</button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

<div class="mb-3">
    <label class="form-label">Calculated Total</label>
    <input type="text" id="calculatedTotal" class="form-control" readonly>
</div>

<button type="submit" class="btn btn-primary">
    {{ isset($package) ? 'Update Package' : 'Create Package' }}
</button>

<!-- Templates as before -->
<template id="categoryTemplate">
    <div class="category border rounded p-3 mb-3" data-category-index="{index}">
        <div class="d-flex justify-content-between mb-2">
            <input type="text" name="categories[{index}][name]" class="form-control category-name" placeholder="Category Name" required>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeCategory(this)">Remove Category</button>
        </div>
        <div class="itemsContainer">
            <h5>Items</h5>
            <button type="button" class="btn btn-info btn-sm mb-2" onclick="addItem(this)">Add Item</button>
            <div class="itemsList"></div>
        </div>
    </div>
</template>

<template id="itemTemplate">
    <div class="item d-flex gap-2 mb-2 align-items-start" data-item-index="{itemIndex}">
        <input type="text" name="categories[{categoryIndex}][items][{itemIndex}][name]" class="form-control item-name" placeholder="Item Name" required>
        <input type="number" name="categories[{categoryIndex}][items][{itemIndex}][quantity]" class="form-control item-quantity" placeholder="Qty" min="1" value="1" required>
        <input type="number" name="categories[{categoryIndex}][items][{itemIndex}][price]" class="form-control item-price" placeholder="Price" step="0.01" min="0">
        <input type="text" name="categories[{categoryIndex}][items][{itemIndex}][description]" class="form-control item-description" placeholder="Description (optional)">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">X</button>
    </div>
</template>

<script>
let categoryCount = {{ isset($package) ? count(old('categories', $package->categories->toArray())) : 0 }};

function addCategory() {
    const container = document.getElementById('categoriesContainer');
    const template = document.getElementById('categoryTemplate').innerHTML.replace(/{index}/g, categoryCount);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template;
    container.appendChild(wrapper);
    categoryCount++;
    updateTotalPrice();
}

function removeCategory(button) {
    button.closest('.category').remove();
    updateTotalPrice();
}

function addItem(button) {
    const categoryDiv = button.closest('.category');
    const categoryIndex = categoryDiv.getAttribute('data-category-index');
    const itemsList = categoryDiv.querySelector('.itemsList');

    const itemIndex = itemsList.children.length;
    const itemTemplate = document.getElementById('itemTemplate').innerHTML
        .replace(/{categoryIndex}/g, categoryIndex)
        .replace(/{itemIndex}/g, itemIndex);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = itemTemplate;
    itemsList.appendChild(wrapper);
    updateTotalPrice();
}

function removeItem(button) {
    button.closest('.item').remove();
    updateTotalPrice();
}

document.addEventListener('input', updateTotalPrice);

function updateTotalPrice() {
    let total = 0;
    const items = document.querySelectorAll('.item');
    items.forEach(item => {
        const qty = parseFloat(item.querySelector('.item-quantity')?.value || 0);
        const price = parseFloat(item.querySelector('.item-price')?.value || 0);
        total += qty * price;
    });

    document.getElementById('calculatedTotal').value = total.toFixed(2);
}

window.onload = updateTotalPrice;
</script>
