<x-layouts.funeral>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white">Inventory Items</h2>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('funeral.items.export', request()->all()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <a href="{{ route('funeral.items.create') }}" class="btn btn-success">
                    + Add Item
                </a>
                <a href="{{ route('funeral.partnerships.resource_requests.index') }}" class="btn btn-warning">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    View Resource Requests
                </a>
                <a href="{{ route('funeral.assets.reservations.index') }}" class="btn btn-info">
                    <i class="bi bi-calendar-event me-1"></i>
                    Asset Booking Management
                </a>
            </div>
        </div>

<div class="mb-4">
    <form id="filterForm" method="GET" action="{{ route('funeral.items.index') }}" class="row g-2 align-items-end flex-nowrap">
        <div class="col" style="min-width:220px;max-width:320px;">
            <input type="text" name="search" value="{{ request('search') }}"
                class="form-control"
                placeholder="Search by name or brand">
        </div>
        <div class="col-auto" style="min-width:160px;max-width:190px;">
            <select name="category" class="form-select">
                <option value="all" {{ request('category', 'all') == 'all' ? 'selected' : '' }}>All Categories</option>
                <option value="none" {{ request('category') == 'none' ? 'selected' : '' }}>Uncategorized</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto" style="min-width:140px;max-width:170px;">
            <select name="status" class="form-select">
                <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>All Status</option>
                <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                <option value="shared_to_partner" {{ request('status') == 'shared_to_partner' ? 'selected' : '' }}>Shared to Partner</option>
                <option value="borrowed_from_partner" {{ request('status') == 'borrowed_from_partner' ? 'selected' : '' }}>Borrowed from Partner</option>
            </select>
        </div>
        <div class="col-auto d-flex align-items-center">
            <div class="form-check me-2">
                <input class="form-check-input" type="checkbox" name="shareable_only" id="shareable_only"
                    value="1" {{ request('shareable_only') ? 'checked' : '' }}>
                <label class="form-check-label" for="shareable_only" style="font-size: 0.97em;">
                    Shareable Only
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="low_stock_only" id="low_stock_only"
                    value="1" {{ request('low_stock_only') ? 'checked' : '' }}>
                <label class="form-check-label" for="low_stock_only" style="font-size: 0.97em;">
                    Low Stock Only
                </label>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary px-4" style="min-width: 90px;">Search</button>
        </div>
    </form>
</div>



        <div id="ajax-table">
            @include('funeral.items._table', ['items' => $items])
        </div>
    </div>

    @push('scripts')
    <script>
    $(function(){
        // AJAX search/filter submit
        $('#filterForm').on('change submit', function(e){
            e.preventDefault();
            $.get("{{ route('funeral.items.index') }}", $(this).serialize(), function(data){
                $('#ajax-table').html($(data).find('#ajax-table').html());
            });
        });

        // AJAX pagination
        $(document).on('click', '#ajax-table .pagination a', function(e){
            e.preventDefault();
            var url = $(this).attr('href');
            $.get(url, $('#filterForm').serialize(), function(data){
                $('#ajax-table').html($(data).find('#ajax-table').html());
            });
        });

        // AJAX delete
        $(document).on('submit', 'form.ajax-delete', function(e){
            e.preventDefault();
            if(!confirm('Are you sure you want to delete this item?')) return;
            let $form = $(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function () {
                    $('#filterForm').trigger('submit');
                    window.dispatchEvent(new CustomEvent('ajax-flash', {detail:{type:'success',message:'Item deleted!'}}));
                },
                error: function () {
                    window.dispatchEvent(new CustomEvent('ajax-flash', {detail:{type:'error',message:'Failed to delete item'}}));
                }
            });
        });
    });



    </script>
    @endpush
</x-layouts.funeral>
