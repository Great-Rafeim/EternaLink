<x-layouts.funeral>
    <div class="container py-4">
        <h1 class="h3 mb-4 text-white fw-bold">Funeral Parlor Dashboard</h1>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Inventory Items</h5>
                        <p class="card-text display-6">{{ $totalItems }}</p>
                        <a href="{{ route('funeral.items.index') }}" class="btn btn-outline-light btn-sm mt-2">Manage Items</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Low Stock Alerts</h5>
                        <p class="card-text display-6 text-warning">{{ $lowStockCount }}</p>
                        <a href="{{ route('funeral.items.index') }}" class="btn btn-outline-warning btn-sm mt-2">Review Stock</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Categories</h5>
                        <p class="card-text display-6">{{ $categoryCount }}</p>
                        <a href="{{ route('funeral.categories.index') }}" class="btn btn-outline-light btn-sm mt-2">View Categories</a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Packages</h5>
                        <p class="card-text display-6">{{ $packageCount }}</p>
                        <a href="{{ route('funeral.packages.index') }}" class="btn btn-outline-light btn-sm mt-2">Browse Packages</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.funeral>
