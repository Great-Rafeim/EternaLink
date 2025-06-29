{{-- resources/views/components/admin-layout.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? '' }} | Admin Dashboard - EternaLink</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6fb; }
        .eternal-sidebar { background: #171c27; min-height: 100vh; color: #fff; padding-top: 2rem; box-shadow: 2px 0 12px rgba(0,0,0,0.08);}
        .eternal-sidebar .nav-link { color: #b0b6c3; font-weight: 500; border-radius: 0.5rem; transition: background .18s, color .18s;}
        .eternal-sidebar .nav-link.active, .eternal-sidebar .nav-link:hover { background: #24304b; color: #ffc107;}
        .sidebar-heading { letter-spacing: 2px; font-size: 1.1rem; color: #6c757d; margin-bottom: .6rem; padding-left: 1rem;}
        .navbar-brand { font-weight: 700; letter-spacing: 1px; font-size: 1.25rem;}
        .profile-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; margin-right: .75rem;}
        .main-content { min-height: 90vh;}
        .sidebar-collapsed { width: 68px !important; min-width: 68px !important; overflow-x: hidden;}
        .sidebar-collapsed .nav-link span { display: none !important;}
        .sidebar-collapsed .sidebar-heading { display: none;}
        .toggle-sidebar-btn { background: none; border: none; color: #fff; margin-bottom: 1rem; margin-left: .25rem;}
        .notification-dot { width: 10px; height: 10px; background: #ff4545; border-radius: 50%; position: absolute; right: 5px; top: 8px; border: 2px solid #171c27;}
    </style>
</head>
<body>
    <div class="d-flex" id="app-layout">
        
        <nav id="sidebarMenu" class="eternal-sidebar flex-shrink-0 p-0" style="width:240px;transition:width 0.2s;">
            <div class="d-flex flex-column h-100">
                <button class="toggle-sidebar-btn d-md-none d-block ms-2 mt-2" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-2"></i>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="navbar-brand d-flex align-items-center mb-4 ms-3">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i> EternaLink Admin
                </a>
                <div class="sidebar-heading">MAIN</div>
                <ul class="nav flex-column mb-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people-fill me-2"></i><span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('admin.login-history') ? 'active' : '' }}" href="{{ route('admin.login-history') }}">
                            <i class="bi bi-journal-check me-2"></i><span>Login History</span>
                        </a>
                    </li>
                </ul>
                <div class="sidebar-heading mt-auto mb-2">OTHERS</div>
                <ul class="nav flex-column">
                    <li>
                        <a class="nav-link {{ request()->routeIs('notifications.index') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                            <i class="bi bi-bell-fill me-2 position-relative">
                                @if(auth()->user()->unreadNotifications->count())
                                    <span class="notification-dot"></span>
                                @endif
                            </i>
                            <span>Notifications</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="flex-grow-1 d-flex flex-column min-vh-100" style="background: #f4f6fb;">
            {{-- Top Navbar --}}
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top" style="z-index:1001;">
                <button class="d-md-none btn btn-link me-3" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <a class="navbar-brand d-none d-md-block" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-lightning-charge-fill text-warning me-2"></i> EternaLink Admin
                </a>

                <div class="flex-grow-1"></div>

                {{-- Notification Bell --}}
                <div class="dropdown me-3">
                    <button class="btn btn-link position-relative" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell-fill fs-5"></i>
                        @if(auth()->user()->unreadNotifications->count())
                            <span class="notification-dot"></span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notificationsDropdown" style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="dropdown-header text-dark fw-bold">Notifications</li>
                        @forelse(auth()->user()->unreadNotifications->take(10) as $notification)
                            <li class="px-3 py-2 border-bottom small">
                                <div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                <div>{{ $notification->data['message'] ?? '' }}</div>
                                <div class="text-muted mt-1" style="font-size: .75rem;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>
                            </li>
                        @empty
                            <li class="px-3 py-4 text-center text-muted">No new notifications</li>
                        @endforelse
                        <li>
                            <a class="dropdown-item text-primary text-center" href="{{ route('notifications.index') }}">View All</a>
                        </li>
                    </ul>
                </div>

                {{-- User Dropdown --}}
                <div class="dropdown">
                    <a class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=3d3e4b&color=fff&rounded=true&bold=true" alt="Profile" class="profile-img">
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-2"></i>Profile Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            {{-- Main Content --}}
            <main class="container-fluid main-content py-4 px-2 px-md-4">
                {{ $slot }}
            </main>

            {{-- Footer --}}
            <footer class="text-center py-4 text-muted small mt-auto bg-transparent">
                &copy; {{ date('Y') }} <b>EternaLink Admin Panel</b>. All rights reserved.
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebarMenu');
            sidebar.classList.toggle('sidebar-collapsed');
        }
    </script>
</body>
</html>
