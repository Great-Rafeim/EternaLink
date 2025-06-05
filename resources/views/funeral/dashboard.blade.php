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

        <!-- Recent Notifications -->
        <div class="card text-bg-dark border-secondary shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent Notifications</span>
                <a href="{{ route('funeral.notifications.index') }}" class="btn btn-sm btn-outline-info">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($recentNotifications as $notification)
                    <div class="list-group-item list-group-item-dark d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold">{{ $notification->title }}</div>
                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        <a href="{{ $notification->action_url }}" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No recent notifications.</div>
                @endforelse

                <!-- Pagination -->
                <div class="px-3 py-2">
                    {{ $recentNotifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.funeral>
