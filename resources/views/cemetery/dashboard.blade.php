<x-cemetery-layout>
    <div class="mb-4">
        <h2 class="h2 fw-bold mb-1 text-white">Welcome, {{ Auth::user()->name }}!</h2>
        <p class="text-secondary mb-3 fs-5">Cemetery Management Dashboard</p>
        <div class="bg-dark border-start border-4 border-secondary rounded-3 p-4 mb-4">
            <span class="fw-semibold text-white-50">
                Here you can monitor all your plots, manage bookings, and stay on top of your operations.
            </span>
        </div>
    </div>

    {{-- Quick Summary Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-12 col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-start p-4">
                    <div class="text-secondary fw-semibold d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-square"></i> Available Plots
                    </div>
                    <div class="display-5 fw-bolder text-white mb-1">
                        {{ $availablePlots ?? '0' }}
                    </div>
                    <a href="{{ route('cemetery.plots.index', ['status' => 'available']) }}"
                        class="text-secondary small text-decoration-underline">View Details</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-start p-4">
                    <div class="text-secondary fw-semibold d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-bookmark-star"></i> Reserved Plots
                    </div>
                    <div class="display-5 fw-bolder text-white mb-1">
                        {{ $reservedPlots ?? '0' }}
                    </div>
                    <a href="{{ route('cemetery.plots.index', ['status' => 'reserved']) }}"
                        class="text-secondary small text-decoration-underline">View Details</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-start p-4">
                    <div class="text-secondary fw-semibold d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-person-fill-down"></i> Occupied Plots
                    </div>
                    <div class="display-5 fw-bolder text-white mb-1">
                        {{ $occupiedPlots ?? '0' }}
                    </div>
                    <a href="{{ route('cemetery.plots.index', ['status' => 'occupied']) }}"
                        class="text-secondary small text-decoration-underline">View Details</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-start p-4">
                    <div class="text-secondary fw-semibold d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calendar-event"></i> Pending Bookings
                    </div>
                    <div class="display-5 fw-bolder text-white mb-1">
                        {{ $pendingBookings ?? '0' }}
                    </div>
                    <a href="{{ route('cemetery.bookings.index', ['status' => 'pending']) }}"
                        class="text-secondary small text-decoration-underline">Review Now</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="d-flex flex-wrap gap-3 mb-5">
        <a href="{{ route('cemetery.plots.index') }}"
           class="card bg-dark border-0 shadow-sm px-5 py-3 rounded-3 text-white text-decoration-none d-flex align-items-center gap-2">
            <i class="bi bi-grid-3x3-gap-fill"></i> Manage Plots
        </a>
        <a href="{{ route('cemetery.bookings.index') }}"
           class="card bg-dark border-0 shadow-sm px-5 py-3 rounded-3 text-white text-decoration-none d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check"></i> View Bookings
        </a>
    </div>

    {{-- Recent Activity 
    <div class="card bg-dark border-0 rounded-3 shadow-sm p-4">
        <div class="d-flex align-items-center mb-3">
            <i class="bi bi-activity fs-4 text-secondary me-2"></i>
            <span class="fw-semibold text-white-50 fs-5">Recent Activity</span>
        </div>
        @if(isset($recentActivities) && count($recentActivities))
            <ul class="list-group list-group-flush">
                @foreach($recentActivities as $activity)
                    <li class="list-group-item bg-transparent d-flex align-items-center gap-3 border-0 px-0">
                        <i class="bi bi-dot text-secondary fs-3"></i>
                        <span class="text-white-50">{{ $activity->description }}</span>
                        <span class="ms-auto text-secondary small">{{ $activity->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-secondary small">No recent activity.</p>
        @endif
    </div>--}}
</x-cemetery-layout>
