<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'EternaLink') }} - Cemetery Admin Portal</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Styles -->
    <style>[x-cloak] { display: none !important; }</style>
    <style>
body {
    background: #22272e;
    color: #f7fafc;
    min-height: 100vh;
    font-family: 'Figtree', sans-serif;
}
.navbar {
    background-color: #252b33 !important;
}
.navbar-brand,
.nav-link,
.dropdown-toggle {
    color: #f7fafc !important;
}
.nav-link.active,
.nav-link:hover {
    color: #60a5fa !important; /* blue accent instead of green */
}
header, footer {
    background-color: #252b33 !important;
}
footer {
    color: #bbb;
}
.sidebar {
    min-height: 100vh;
    background: #23282f;
    border-right: 1px solid #21262c;
}
.sidebar .nav-link {
    color: #f7fafc !important;
    border-radius: 0.5rem;
    margin-right: 0.25rem;
    margin-left: 0.25rem;
}
.sidebar .nav-link.active,
.sidebar .nav-link:hover {
    background: #1e293b;
    color: #60a5fa !important; /* blue accent */
}
.sidebar .nav-link.active {
    font-weight: 600;
    background: #1e293b !important;
}
@media (max-width: 991.98px) {
    .sidebar {
        min-height: auto;
        position: fixed;
        top: 56px;
        left: 0;
        height: 100%;
        z-index: 1040;
        transform: translateX(-100%);
        transition: transform 0.2s;
        width: 220px;
        box-shadow: 2px 0 10px rgba(0,0,0,0.13);
    }
    .sidebar.show-sidebar {
        transform: translateX(0);
    }
    .sidebar-backdrop {
        display: block;
        position: fixed;
        top: 56px;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.3);
        z-index: 1039;
    }
}
.form-control, .form-select {
    background: #21262c;
    border-color: #2d3748;
    color: #f7fafc;
}
.form-control:focus, .form-select:focus {
    background: #252b33;
    color: #fff;
    border-color: #60a5fa; /* blue accent for focus */
    box-shadow: none;
}
.dropdown-menu {
    background: #f7fafc;
    color: #f7fafc;
}
.dropdown-item {
    color: #23282f;
}
.dropdown-item:hover, .dropdown-item.active {
    background: #f7fafc;
    color: #60a5fa;
}
.bg-success, .bg-danger {
    color: #fff !important;
}

    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="d-flex flex-column">

<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container-fluid">
        <button class="btn btn-dark d-lg-none me-2" id="sidebarToggle">
            <i class="bi bi-list" style="font-size: 1.5rem"></i>
        </button>
        <a class="navbar-brand fw-bold" href="{{ route('funeral.dashboard') }}">
            FuneralParlor
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#funeralNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="funeralNavbar">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Notification Bell -->
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        @if(auth()->user()->unreadNotifications->count())
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="fw-bold text-secondary px-2 mb-2">Notifications</li>
                        @forelse(auth()->user()->unreadNotifications->take(8) as $notification)
                            <li>
                                <a href="{{ route('notifications.redirect', $notification->id) }}"
                                   class="dropdown-item d-flex flex-column small py-2 {{ $notification->read_at ? '' : 'bg-light' }}"
                                   style="white-space: normal;">
                                    <span>{!! $notification->data['message'] ?? 'Notification' !!}</span>
                                    <span class="text-muted">{{ $notification->created_at->diffForHumans() }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="dropdown-item text-muted">No new notifications</li>
                        @endforelse
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">
                                View All Notifications
                            </a>
                        </li>
                    </ul>
                </li>
                <!-- Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i> {{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-2"></i> Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar -->
        <div id="sidebar" class="col-auto col-md-3 col-lg-2 px-sm-2 px-0 sidebar d-flex flex-column show-sidebar">
            <ul class="nav nav-pills flex-column mb-auto py-4">
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('cemetery.dashboard') ? 'active' : '' }}"
                        href="{{ route('cemetery.dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('cemetery.plots.*') ? 'active' : '' }}"
                    href="{{ route('cemetery.plots.index') }}">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i> Plots Management
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('cemetery.bookings.*') ? 'active' : '' }}"
                        href="{{ route('cemetery.bookings.index') }}">
                        <i class="bi bi-calendar-check me-2"></i> Bookings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('cemetery.profile.*') ? 'active' : '' }}"
                        href="{{ route('cemetery.profile.edit') }}">
                        <i class="bi bi-person me-2"></i> Cemetery Profile
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col py-4">
            @isset($header)
            <header class="py-4 border-bottom shadow-sm mb-4">
                <div class="container">
                    <h1 class="h4" style="color:#f7fafc;">{{ $header }}</h1>
                </div>
            </header>
            @endisset

            <main class="flex-grow-1">
                {{ $slot }}
            </main>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="py-3 text-center mt-auto border-top">
    &copy; {{ date('Y') }} EternaLink Cemetery Admin Portal. All rights reserved.
</footer>

@if (session('success'))
    <div id="toast-success" class="toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3 show" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
@endif
@if (session('error'))
    <div id="toast-error" class="toast align-items-center text-white bg-danger border-0 position-fixed top-0 end-0 m-3 show" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('error') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bootstrap toast auto-show and auto-hide after 3s
    let toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.forEach(function (toastEl) {
        let toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    });

    // Sidebar show/hide on mobile
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    let backdrop = null;

    function showSidebar() {
        sidebar.classList.add('show-sidebar');
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop d-lg-none';
        backdrop.onclick = hideSidebar;
        document.body.appendChild(backdrop);
    }

    function hideSidebar() {
        sidebar.classList.remove('show-sidebar');
        if (backdrop) {
            document.body.removeChild(backdrop);
            backdrop = null;
        }
    }

    toggleBtn.addEventListener('click', function() {
        if (sidebar.classList.contains('show-sidebar')) {
            hideSidebar();
        } else {
            showSidebar();
        }
    });

    // Close sidebar if window is resized to large
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            hideSidebar();
        }
    });

    // Listen for custom AJAX flash messages
    window.addEventListener('ajax-flash', function(e) {
        let type = e.detail.type || 'success';
        let message = e.detail.message || '';
        let toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 position-fixed top-0 end-0 m-3 show`;
        toast.innerHTML = `<div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
        document.body.appendChild(toast);
        let bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        bsToast._element.addEventListener('hidden.bs.toast', () => toast.remove());
    });
});
</script>

<!-- AlpineJS (optional, only if you use x-data/x-cloak somewhere) -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
