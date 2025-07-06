<x-admin-layout>
    <div class="container-fluid">

        {{-- Welcome Header --}}
        <div class="mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="fw-bold mb-1" style="color: #0d1336; letter-spacing:.5px;">
                        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>
                        Welcome back, {{ Auth::user()->name }} <span class="wave">👋</span>
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

        {{-- Quick Stats --}}
        <div class="row g-4 mb-5">
            <div class="col-md-2 col-6">
                <div class="card dashboard-stat shadow-sm border-0 gradient-bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="icon-circle bg-white bg-opacity-25 me-3"><i class="bi bi-people-fill"></i></span>
                            <span class="stat-title">Total Users</span>
                        </div>
                        <div class="display-6 fw-bold">{{ $totalUsers }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card dashboard-stat shadow-sm border-0 gradient-bg-blue text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="icon-circle bg-white bg-opacity-25 me-3"><i class="bi bi-person-fill"></i></span>
                            <span class="stat-title">Clients</span>
                        </div>
                        <div class="display-6 fw-bold">{{ $clientCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card dashboard-stat shadow-sm border-0 gradient-bg-purple text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="icon-circle bg-white bg-opacity-25 me-3"><i class="bi bi-person-badge-fill"></i></span>
                            <span class="stat-title">Agents</span>
                        </div>
                        <div class="display-6 fw-bold">{{ $agentCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card dashboard-stat shadow-sm border-0 gradient-bg-green text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="icon-circle bg-white bg-opacity-25 me-3"><i class="bi bi-truck"></i></span>
                            <span class="stat-title">Funeral Parlors</span>
                        </div>
                        <div class="display-6 fw-bold">{{ $funeralCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card dashboard-stat shadow-sm border-0 gradient-bg-teal text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="icon-circle bg-white bg-opacity-25 me-3"><i class="bi bi-tree-fill"></i></span>
                            <span class="stat-title">Cemeteries</span>
                        </div>
                        <div class="display-6 fw-bold">{{ $cemeteryCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card dashboard-stat shadow-sm border-0 gradient-bg-dark text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-1">
                            <span class="icon-circle bg-white bg-opacity-25 me-3"><i class="bi bi-cash-coin"></i></span>
                            <span class="stat-title">Total Profits</span>
                        </div>
                        <div class="display-6 fw-bold">₱{{ number_format($totalProfit, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Divider --}}
        <hr class="my-5" style="border-top:2px solid #ececec;"/>

        {{-- Panels Row --}}
        <div class="row g-4">
            {{-- Pending Registration Requests --}}
            <div class="col-lg-5 col-xl-4">
                <div class="card shadow border-0 rounded-4 h-100 glass-bg">
                    <div class="card-header bg-white d-flex align-items-center border-0 border-bottom-0">
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
                                    <li class="list-group-item d-flex align-items-center bg-transparent">
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
                <div class="card shadow border-0 rounded-4 h-100 glass-bg">
                    <div class="card-header bg-white d-flex align-items-center border-0 border-bottom-0">
                        <i class="bi bi-journal-check text-success fs-4 me-2"></i>
                        <span class="fw-semibold">Recent Login History</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
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

    <style>
        .dashboard-stat {
            border-radius: 1.1rem;
            transition: box-shadow .18s;
            box-shadow: 0 4px 24px rgba(60,64,84,.07);
            overflow: hidden;
            position: relative;
            min-height: 120px;
        }
        .dashboard-stat .icon-circle {
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            box-shadow: 0 1px 8px rgba(0,0,0,.05);
        }
        .dashboard-stat .stat-title {
            font-size: .98rem;
            font-weight: 500;
            letter-spacing: .5px;
            opacity: .85;
        }
        .gradient-bg-primary  { background: linear-gradient(135deg, #3a45a8 0%, #263399 100%);}
        .gradient-bg-blue     { background: linear-gradient(135deg, #2196f3 0%, #6ec6ff 100%);}
        .gradient-bg-purple   { background: linear-gradient(135deg, #7b1fa2 0%, #ce93d8 100%);}
        .gradient-bg-green    { background: linear-gradient(135deg, #388e3c 0%, #a5d6a7 100%);}
        .gradient-bg-teal     { background: linear-gradient(135deg, #00897b 0%, #80cbc4 100%);}
        .gradient-bg-dark     { background: linear-gradient(135deg, #212121 0%, #757575 100%);}
        .gradient-bg-orange   { background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);}
        .glass-bg {
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(2px);
        }
        .wave {
            animation: wave-animation 2.5s infinite;
            display: inline-block;
            transform-origin: 70% 70%;
        }
        @keyframes wave-animation {
            0% { transform: rotate(0deg);}
            15% { transform: rotate(14deg);}
            30% { transform: rotate(-8deg);}
            40% { transform: rotate(14deg);}
            50% { transform: rotate(-4deg);}
            60% { transform: rotate(10deg);}
            70% { transform: rotate(0deg);}
            100% { transform: rotate(0deg);}
        }
    </style>
</x-admin-layout>
