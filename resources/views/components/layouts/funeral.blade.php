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


    <!-- Icons -->
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
    </style>
</head>

<script src="//unpkg.com/alpinejs" defer></script>

<body class="d-flex flex-column">

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
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('funeral.dashboard') ? 'active' : '' }}"
                        href="{{ route('funeral.dashboard') }}">
                        <i class="bi bi-house-door me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('funeral.items.*') ? 'active' : '' }}"
                        href="{{ route('funeral.items.index') }}">
                        <i class="bi bi-box-seam me-1"></i> Inventory Items
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('funeral.categories.*') ? 'active' : '' }}"
                        href="{{ route('funeral.categories.index') }}">
                        <i class="bi bi-tags me-1"></i> Inventory Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('funeral.packages.*') ? 'active' : '' }}"
                        href="{{ route('funeral.packages.index') }}">
                        <i class="bi bi-briefcase me-1"></i> Packages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('funeral.notifications.*') ? 'active' : '' }}"
                        href="{{ route('funeral.notifications.index') }}">
                        <i class="bi bi-bell me-1"></i> Notifications
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('funeral.partnerships.*') ? 'active' : '' }}"
                        href="{{ route('funeral.partnerships.index') }}">
                        <i class="bi bi-people me-1"></i>
                        Partnerships
                    </a>
                </li>

            </ul>
            
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Notification Bell (summary) -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative dropdown-toggle" href="#" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        @if(auth()->user()->unreadNotifications->count())
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="width: 300px; max-height: 400px; overflow-y: auto;">
                        <li class="fw-bold text-secondary px-2">Notifications</li>
                        @forelse(auth()->user()->unreadNotifications as $notification)
                            <li class="dropdown-item text-wrap text-dark bg-light rounded mb-1">
                                {{ $notification->data['message'] ?? 'Notification' }}
                                <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                            </li>
                        @empty
                            <li class="dropdown-item text-muted">No new notifications</li>
                        @endforelse
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


</body>
</html>
