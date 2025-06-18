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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    

    <!-- Custom Styles -->
    <style>[x-cloak] { display: none !important; }</style>
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
        .sidebar {
            min-height: 100vh;
            background: #1f2937;
            border-right: 1px solid #222;
        }
        .sidebar .nav-link {
            color: #f8f9fa !important;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: #21263b;
            color: #ffc107 !important;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                min-height: auto;
            }
        }
        /* Hide sidebar on small screens by default */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 56px; /* height of navbar */
                left: 0;
                height: 100%;
                z-index: 1040;
                transform: translateX(-100%);
                transition: transform 0.2s;
                width: 220px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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

    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>

<script src="//unpkg.com/alpinejs" defer></script>

<body class="d-flex flex-column">

<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container-fluid">

        <!-- Sidebar Toggle (Hamburger) -->
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

                <!-- Notification Bell (summary) -->
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
                    <a class="nav-link {{ request()->routeIs('funeral.dashboard') ? 'active' : '' }}"
                        href="{{ route('funeral.dashboard') }}">
                        <i class="bi bi-house-door me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.bookings.*') ? 'active' : '' }}"
                    href="{{ route('funeral.bookings.index') }}">
                        <i class="bi bi-calendar2-check me-2"></i> Client Bookings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.items.*') ? 'active' : '' }}"
                        href="{{ route('funeral.items.index') }}">
                        <i class="bi bi-box-seam me-2"></i> Inventory Items
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.categories.*') ? 'active' : '' }}"
                        href="{{ route('funeral.categories.index') }}">
                        <i class="bi bi-tags me-2"></i> Inventory Categories
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.packages.*') ? 'active' : '' }}"
                        href="{{ route('funeral.packages.index') }}">
                        <i class="bi bi-briefcase me-2"></i> Packages
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.partnerships.*') ? 'active' : '' }}"
                        href="{{ route('funeral.partnerships.index') }}">
                        <i class="bi bi-people me-2"></i>
                        Partnerships
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.profile.*') ? 'active' : '' }}"
                        href="{{ route('funeral.profile.edit') }}">
                        <i class="bi bi-person me-2"></i>
                        Funeral Parlor Profile
                    </a>
                </li>

                <!-- AGENT MANAGEMENT NAV ITEM -->
                @if(auth()->user()->role === 'funeral')
                <li class="nav-item mb-2">
                    <a class="nav-link {{ request()->routeIs('funeral.agents.*') ? 'active' : '' }}"
                        href="{{ route('funeral.agents.index') }}">
                        <i class="bi bi-person-badge me-2"></i> Agents
                    </a>
                </li>
                @endif

            </ul>
        </div>

        <!-- Main Content -->
        <div class="col py-4">
            <!-- Page Heading -->
            @isset($header)
            <header class="py-4 border-bottom shadow-sm mb-4">
                <div class="container">
                    <h1 class="h4 text-white">{{ $header }}</h1>
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
    &copy; {{ date('Y') }} Funeral Parlor Management. All rights reserved.
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function(){
    // AJAX search/filter submit
    $('#filterForm').on('change submit', function(e){
        e.preventDefault();
        $.get("{{ route('funeral.items.index') }}", $(this).serialize(), function(data){
            $('#ajax-table').html($(data).find('#ajax-table').html());
            // Optional: show a toast
            // window.dispatchEvent(new CustomEvent('ajax-flash', {detail:{type:'success',message:'Results updated'}}));
        });
    });

    // AJAX pagination
    $(document).on('click', '#ajax-table .pagination a', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        $.get(url, $('#filterForm').serialize(), function(data){
            $('#ajax-table').html($(data).find('#ajax-table').html());
        });
    });

    // AJAX delete
    $(document).on('submit', 'form.ajax-delete', function(e){
        e.preventDefault();
        if(!confirm('Are you sure you want to delete this item?')) return;
        let $form = $(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function (data) {
                // Reload items (optionally, reload only the row or table)
                $('#filterForm').trigger('submit');
                window.dispatchEvent(new CustomEvent('ajax-flash', {detail:{type:'success',message:'Item deleted!'}}));
            },
            error: function () {
                window.dispatchEvent(new CustomEvent('ajax-flash', {detail:{type:'error',message:'Failed to delete item'}}));
            }
        });
    });
});
</script>

<script src="//unpkg.com/alpinejs" defer></script>

<script>
    function toggleShareableQty() {
        var shareableCheckbox = document.getElementById('shareable');
        var qtyGroup = document.getElementById('shareableQtyGroup');
        qtyGroup.style.display = shareableCheckbox.checked ? 'block' : 'none';
    }

    document.getElementById('shareable').addEventListener('change', toggleShareableQty);

    // On page load: show if checked (e.g., during edit or validation error)
    window.addEventListener('DOMContentLoaded', function() {
        toggleShareableQty();
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
});
</script>
 <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.6/dist/signature_pad.umd.min.js"></script>


</body>
</html>
