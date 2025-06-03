<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ config('app.name', 'Funeral Parlor') }} - Funeral Staff</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- Custom Styles -->
    <style>
        body {
            background: linear-gradient(to bottom right, #1f2937, #111827);
            color: #f8f9fa;
            min-height: 100vh;
            font-family: 'Figtree', sans-serif;
        }
        .navbar {
            background-color: #111827;
        }
        .navbar-brand,
        .nav-link,
        .dropdown-toggle {
            color: #f8f9fa !important;
        }
        .nav-link.active,
        .nav-link:hover {
            color: #ffc107 !important;
        }
        header, footer {
            background-color: #1f2937;
        }
        footer {
            color: #aaa;
        }
    </style>
</head>
<body class="d-flex flex-column">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('funeral.dashboard') }}">
            FuneralParlor
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#funeralNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="funeralNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('funeral.packages.*') ? 'active' : '' }}" href="{{ route('funeral.packages.index') }}">Packages</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('funeral.schedules.*') ? 'active' : '' }}" href="{{ route('funeral.schedules.index') }}">Schedules</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('funeral.clients.*') ? 'active' : '' }}" href="{{ route('funeral.clients.index') }}">Clients</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('funeral.staff.*') ? 'active' : '' }}" href="{{ route('funeral.staff.index') }}">Staff</a></li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i> {{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-gear me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Heading -->
@isset($header)
<header class="py-4 border-bottom shadow-sm mb-4">
    <div class="container">
        <h1 class="h4 text-white">{{ $header }}</h1>
    </div>
</header>
@endisset

<!-- Main Content -->
<main class="flex-grow-1 container py-4">
    {{ $slot }}
</main>

<!-- Footer -->
<footer class="py-3 text-center mt-auto border-top">
    &copy; {{ date('Y') }} Funeral Parlor Management. All rights reserved.
</footer>

</body>
</html>
