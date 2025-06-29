<x-admin-layout>
    <div class="container-fluid">
        {{-- Welcome Header --}}
        <div class="mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="fw-bold mb-1" style="color: #1a237e;">
                        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
                        Welcome back, {{ Auth::user()->name }} 👋
                    </h2>
                    <div class="text-muted">Here’s an overview of your system.</div>
                </div>
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary position-relative">
                    <i class="bi bi-bell-fill"></i> Notifications
                    @if(auth()->user()->unreadNotifications->count())
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    @endif
                </a>
            </div>
        </div>

        {{-- Quick Stats Cards --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-sm-6">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #3949ab 0%, #1a237e 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-uppercase fw-semibold text-white-50 small mb-1">Total Users</div>
                            <div class="display-6 fw-bold text-white">{{ $totalUsers }}</div>
                        </div>
                        <span class="ms-auto"><i class="bi bi-people-fill display-4 text-white opacity-75"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #1565c0 0%, #29b6f6 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-uppercase fw-semibold text-white-50 small mb-1">Clients</div>
                            <div class="display-6 fw-bold text-white">{{ $clientCount }}</div>
                        </div>
                        <span class="ms-auto"><i class="bi bi-person-fill display-4 text-white opacity-75"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #8e24aa 0%, #c158dc 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-uppercase fw-semibold text-white-50 small mb-1">Agents</div>
                            <div class="display-6 fw-bold text-white">{{ $agentCount }}</div>
                        </div>
                        <span class="ms-auto"><i class="bi bi-person-badge-fill display-4 text-white opacity-75"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-uppercase fw-semibold text-white-50 small mb-1">Funeral Parlors</div>
                            <div class="display-6 fw-bold text-white">{{ $funeralCount }}</div>
                        </div>
                        <span class="ms-auto"><i class="bi bi-truck display-4 text-white opacity-75"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-uppercase fw-semibold text-white-50 small mb-1">Cemeteries</div>
                            <div class="display-6 fw-bold text-white">{{ $cemeteryCount }}</div>
                        </div>
                        <span class="ms-auto"><i class="bi bi-tree-fill display-4 text-white opacity-75"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-sm-6">
                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #212121 0%, #757575 100%);">
                    <div class="card-body d-flex align-items-center">
                        <div>
                            <div class="text-uppercase fw-semibold text-white-50 small mb-1">Recent Logins</div>
                            <div class="display-6 fw-bold text-white">{{ $logins->count() }}</div>
                        </div>
                        <span class="ms-auto"><i class="bi bi-clock-history display-4 text-white opacity-75"></i></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dashboard Main Panels --}}
        <div class="row g-4">
            {{-- Pending Registration Requests --}}
            <div class="col-lg-5 col-xl-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-white d-flex align-items-center">
                        <i class="bi bi-person-check-fill text-primary fs-4 me-2"></i>
                        <span class="fw-semibold">Pending Registration Requests</span>
                        <span class="badge rounded-pill bg-danger ms-auto">
                            {{ $pendingRequests->count() ?? 0 }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        @if(isset($pendingRequests) && $pendingRequests->count())
                            <ul class="list-group list-group-flush">
                                @foreach($pendingRequests as $request)
                                    <li class="list-group-item d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $request->name }}
                                                <span class="badge bg-secondary text-capitalize ms-2">{{ $request->role }}</span>
                                            </div>
                                            <div class="small text-muted">{{ $request->email }}</div>
                                            <div class="small text-muted">Applied: {{ $request->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div class="ms-auto d-flex gap-2">
                                            <a href="{{ route('admin.users.show', $request->id) }}" class="btn btn-outline-primary btn-sm" title="Review">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-person-dash display-6"></i>
                                <p class="mb-0">No pending requests at the moment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recent Login History --}}
            <div class="col-lg-7 col-xl-8">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-white d-flex align-items-center">
                        <i class="bi bi-journal-check text-success fs-4 me-2"></i>
                        <span class="fw-semibold">Recent Login History</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Login Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logins as $login)
                                        <tr>
                                            <td>{{ $login->user->name }}</td>
                                            <td>{{ $login->user->email }}</td>
                                            <td class="text-capitalize">{{ $login->user->role }}</td>
                                            <td>{{ $login->created_at->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No login history found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> {{-- end row --}}

    </div>
</x-admin-layout>
