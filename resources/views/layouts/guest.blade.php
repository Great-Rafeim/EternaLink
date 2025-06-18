<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EternaLink') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Styles -->
    <style>
        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #6a7688 100%);
        }
        .brand {
            font-size: 2rem;
            font-weight: 700;
            color: #ffc107;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }
        .guest-card {
            border: none;
            border-radius: 1.5rem;
            background: rgba(34, 40, 49, 0.98);
            box-shadow: 0 8px 32px 0 rgba(31, 41, 55, 0.2);
            padding: 2.5rem 2rem;
        }
        .guest-card .card-body {
            color: #fff;
        }
        .brand-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            background: #22242e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .guest-link {
            color: #ffc107;
        }
        .guest-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="d-flex flex-column min-vh-100 justify-content-center align-items-center py-4">
        <div class="brand-logo mb-2">
            <i class="bi bi-gem"></i>
            {{-- or use <x-application-logo class="w-12 h-12" /> --}}
        </div>
        <div class="brand">{{ config('app.name', 'EternaLink') }}</div>
        <div class="w-100" style="max-width: 430px;">
            <div class="card guest-card shadow-lg">
                <div class="card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
