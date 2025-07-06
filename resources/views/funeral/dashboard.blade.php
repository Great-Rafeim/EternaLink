<x-layouts.funeral>
    <div class="container py-4">
        <h1 class="h3 mb-4 text-white fw-bold">Funeral Parlor Dashboard</h1>

        <!-- Summary Cards Row 1 -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Inventory Items</h5>
                        <p class="card-text display-6">{{ $totalItems }}</p>
                        <a href="{{ route('funeral.items.index') }}" class="btn btn-outline-light btn-sm mt-2">Manage Items</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-dark border-warning shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Low Stock Alerts</h5>
                        <p class="card-text display-6 text-warning">{{ $lowStockCount }}</p>
                        <a href="{{ route('funeral.items.index') }}" class="btn btn-outline-warning btn-sm mt-2">Review Stock</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Categories</h5>
                        <p class="card-text display-6">{{ $categoryCount }}</p>
                        <a href="{{ route('funeral.categories.index') }}" class="btn btn-outline-light btn-sm mt-2">View Categories</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-dark border-secondary shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Packages</h5>
                        <p class="card-text display-6">{{ $packageCount }}</p>
                        <a href="{{ route('funeral.packages.index') }}" class="btn btn-outline-light btn-sm mt-2">Browse Packages</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards Row 2 -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="card border-info text-bg-dark shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2">In Process</h6>
                        <p class="display-6 mb-1 text-info">{{ $pendingCount }}</p>
                        <a href="{{ route('funeral.bookings.index') }}" class="btn btn-outline-info btn-sm">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-primary text-bg-dark shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2">Ongoing Bookings</h6>
                        <p class="display-6 mb-1 text-primary">{{ $ongoingCount }}</p>
                        <a href="{{ route('funeral.bookings.index', ['status' => 'ongoing']) }}" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-success text-bg-dark shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2">Completed Bookings</h6>
                        <p class="display-6 mb-1 text-success">{{ $completedCount }}</p>
                        <a href="{{ route('funeral.bookings.index', ['status' => 'completed']) }}" class="btn btn-outline-success btn-sm">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-secondary text-bg-dark shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2">Partners</h6>
                        <p class="display-6 mb-1">{{ $partnerCount }}</p>
                        <a href="{{ route('funeral.partnerships.index') }}" class="btn btn-outline-light btn-sm">Manage Partners</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-secondary text-bg-dark shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2">Active Agents</h6>
                        <p class="display-6 mb-1">{{ $agentCount }}</p>
                        <a href="{{ route('funeral.agents.index') }}" class="btn btn-outline-light btn-sm">View Agents</a>
                    </div>
                </div>
            </div>
        </div>

<!-- Notifications & Recent Bookings Row -->
<div class="row g-4">
<!-- Notifications -->
<div class="col-md-7">
    <div class="card border-secondary text-bg-dark shadow-sm h-100">
        <div class="card-header d-flex align-items-center">
            <i class="bi bi-bell-fill me-2"></i>
            <span>Recent Notifications</span>
        </div>
        <ul class="list-group list-group-flush">
            @forelse($recentNotifications as $notification)
                <li class="list-group-item bg-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            {{-- Robust rendering of notification message --}}
                            {!! $notification->data['message']
                                ?? $notification->data['body']
                                ?? 'You have a new notification.' !!}
                        </div>
                        <small class="text-muted">
                            {{ $notification->created_at ? $notification->created_at->diffForHumans() : '' }}
                        </small>
                    </div>
                </li>
            @empty
                <li class="list-group-item bg-dark text-white text-center">
                    <em>No notifications yet.</em>
                </li>
            @endforelse
        </ul>
        <div class="card-footer text-end bg-dark">
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-light btn-sm">
                View All Notifications
            </a>
        </div>
    </div>
</div>

    <!-- Recent Bookings Table -->
    <div class="col-md-5">
        <div class="card border-secondary text-bg-dark shadow-sm h-100">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-calendar-event me-2"></i>
                <span>Recent Bookings</span>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings->take(5) as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ $booking->client->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $booking->package->name ?? 'N/A' }}
                                    @if($booking->customized_package_id)
                                        <span class="badge bg-info ms-1">Custom</span>
                                    @endif
                                </td>
                                <td>
                                    @php $label = $booking->statusLabel(); @endphp
                                    <span class="badge bg-{{ $label['color'] ?? 'secondary' }}">
                                        <i class="bi bi-{{ $label['icon'] ?? 'question-circle' }}"></i>
                                        {{ $label['label'] ?? ucfirst($booking->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $booking->created_at ? $booking->created_at->format('Y-m-d') : '' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('funeral.bookings.show', $booking->id) }}"
                                       class="btn btn-outline-light btn-sm" title="View Booking">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No recent bookings.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end bg-dark">
                <a href="{{ route('funeral.bookings.index') }}" class="btn btn-outline-light btn-sm">
                    View All Bookings
                </a>
            </div>
        </div>
    </div>
</div>

        {{-- Optional: recent bookings, etc. --}}

    </div>
</x-layouts.funeral>
