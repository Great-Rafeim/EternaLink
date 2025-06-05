<x-layouts.funeral>
<div class="container py-4" x-data="packageBuilder({{ $categories->toJson() }})">
    <h2>Create Funeral Service Package</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('funeral.packages.store') }}">
        @csrf

        <!-- Package Name, Description, (Total Price) -->
        <div class="mb-3">
            <label class="form-label">Package Name</label>
            <input type="text" name="name" class="form-control" x-model="form.name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" x-model="form.description"></textarea>
        </div>

        <!-- Add Category Button -->
        <div class="mb-3">
            <button type="button" class="btn btn-info" @click="showCategoryModal = true">Add Category</button>
        </div>

        <!-- Selected Categories and Items -->
        <template x-for="(cat, cidx) in form.selectedCategories" :key="cat.id">
            <div class="mb-3 border rounded p-3 bg-dark shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-2 text-warning" x-text="cat.name"></h5>
                    <button type="button" class="btn btn-sm btn-danger" @click="removeCategory(cat.id)">Remove</button>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-secondary mb-2" @click="openItemModal(cat)">Add item(s)</button>
                </div>
                <div>
                    <template x-for="(item, iidx) in cat.selectedItems" :key="item.id">
                        <div class="d-flex align-items-center mb-2 gap-2">
                            <span class="text-white" x-text="item.name"></span>
                            <span class="badge bg-secondary ms-2" x-text="'₱' + parseFloat(item.selling_price).toFixed(2)"></span>
                            <input type="number" min="1" class="form-control" style="width:80px"
                                :name="'items['+cat.id+']['+item.id+'][quantity]'"
                                x-model="item.quantity"
                                @input="updateTotal"
                                required>
                            <button type="button" class="btn btn-outline-danger btn-sm" @click="removeItem(cat.id, item.id)">Remove</button>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Calculated Price -->
        <div class="mb-3">
            <label class="form-label">Calculated Total</label>
            <input type="text" class="form-control" :value="'₱' + totalPrice.toFixed(2)" readonly>
        </div>

        <button type="submit" class="btn btn-primary">
            Create Package
        </button>

        <!-- Category Modal -->
        <div x-show="showCategoryModal" style="background:rgba(0,0,0,0.6);" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
            <div class="bg-white p-4 rounded shadow" style="min-width:300px;">
                <h5>Select Category</h5>
                <template x-for="cat in availableCategories" :key="cat.id">
                    <div>
                        <button type="button" class="btn btn-outline-primary w-100 my-1" @click="addCategory(cat)"><span x-text="cat.name"></span></button>
                    </div>
                </template>
                <button type="button" class="btn btn-secondary mt-2" @click="showCategoryModal = false">Close</button>
            </div>
        </div>

        <!-- Item Modal -->
        <div x-show="showItemModal" style="background:rgba(0,0,0,0.6);" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
            <div class="bg-white p-4 rounded shadow" style="min-width:300px;">
                <h5>Select Item(s)</h5>
                <template x-for="item in modalItems" :key="item.id">
                    <div>
                        <input type="checkbox" :id="'modal-item-'+item.id" :value="item.id" x-model="selectedModalItems">
                        <label :for="'modal-item-'+item.id" x-text="item.name"></label>
                    </div>
                </template>
                <button type="button" class="btn btn-success mt-2" @click="confirmAddItems">Add Selected</button>
                <button type="button" class="btn btn-secondary mt-2" @click="showItemModal = false">Close</button>
            </div>
        </div>

    </form>
</div>

<!-- Alpine Component -->
<script src="//unpkg.com/alpinejs" defer></script>
<script>
function packageBuilder(categories) {
    return {
        form: {
            name: '',
            description: '',
            selectedCategories: []
        },
        categories: categories,
        showCategoryModal: false,
        showItemModal: false,
        currentCategory: null,
        modalItems: [],
        selectedModalItems: [],
        totalPrice: 0,

        get availableCategories() {
            // Only show categories not yet selected
            let selectedIds = this.form.selectedCategories.map(c => c.id);
            return this.categories.filter(c => !selectedIds.includes(c.id));
        },

        addCategory(cat) {
            this.form.selectedCategories.push({
                id: cat.id,
                name: cat.name,
                selectedItems: []
            });
            this.showCategoryModal = false;
            this.updateTotal();
        },

        removeCategory(catId) {
            this.form.selectedCategories = this.form.selectedCategories.filter(c => c.id !== catId);
            this.updateTotal();
        },

        openItemModal(cat) {
            this.currentCategory = cat;
            // Find category in categories array to get its items
            let catData = this.categories.find(c => c.id === cat.id);
            this.modalItems = catData ? catData.items : [];
            // Preselect already selected items
            this.selectedModalItems = cat.selectedItems.map(i => i.id);
            this.showItemModal = true;
        },

        confirmAddItems() {
            if (!this.currentCategory) return;
            // Find selected items in all items of the category
            let catData = this.categories.find(c => c.id === this.currentCategory.id);
            let items = catData ? catData.items : [];
            // Map to selected items with initial quantity
            this.currentCategory.selectedItems = items
                .filter(i => this.selectedModalItems.includes(i.id))
                .map(i => ({
                    id: i.id,
                    name: i.name,
                    selling_price: i.selling_price,
                    quantity: 1 // default
                }));
            this.showItemModal = false;
            this.updateTotal();
        },

        removeItem(catId, itemId) {
            let cat = this.form.selectedCategories.find(c => c.id === catId);
            if (cat) {
                cat.selectedItems = cat.selectedItems.filter(i => i.id !== itemId);
                this.updateTotal();
            }
        },

        updateTotal() {
            let total = 0;
            this.form.selectedCategories.forEach(cat => {
                cat.selectedItems.forEach(item => {
                    total += (parseFloat(item.selling_price || 0) * (parseInt(item.quantity) || 1));
                });
            });
            this.totalPrice = total;
        }
    };
}
</script>
</x-layouts.funeral>
