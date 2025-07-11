<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'EternaLink Agent Portal' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Bootstrap CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Bootstrap Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', 'Roboto', 'Arial', sans-serif;
        }
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .main-content {
            min-height: 80vh;
        }
        .footer {
            background: #23272b;
            color: #fff;
            padding: 1rem 0;
        }
        .nav-link.active {
            font-weight: bold;
            color: #512da8 !important;
        }
        .navbar {
            box-shadow: 0 2px 8px rgba(81,45,168,0.06);
        }
        .card {
            border-radius: 2rem;
        }
        .btn-primary {
            background: #512da8;
            border: none;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: #311b92;
        }
        .min-vh-70 {
            min-height: 70vh;
        }
    </style>
    {{ $styles ?? '' }}
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    {{-- Navigation Bar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('agent.dashboard') }}">
                <span class="me-2"><i class="bi bi-shield-lock-fill text-primary"></i></span>
                EternaLink Agent
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#agentNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="agentNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}" href="{{ route('agent.dashboard') }}">
                            <i class="bi bi-house-door me-1"></i> Dashboard
                        </a>
                    </li>
                    {{-- If you have or want more menu links for agents, add here --}}
                     {{--
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('agent.bookings.index') ? 'active' : '' }}" href="{{ route('agent.bookings.index') }}">
                            <i class="bi bi-calendar-check me-1"></i> My Bookings
                        </a>
                    </li> --}}
                    
                    {{-- Notifications --}}
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
                    {{-- Profile Dropdown --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person me-1"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <div class="main-content container py-5">
        {{ $slot }}
    </div>

    {{-- Footer --}}
    <footer class="footer text-center mt-auto">
        <div class="container">
            <small>&copy; {{ date('Y') }} EternaLink. All rights reserved.</small>
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    {{ $scripts ?? '' }}
    @stack('scripts')
</body>
</html>
