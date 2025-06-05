<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', 'EternaLink') }} – Cemetery Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-950 via-gray-900 to-black min-h-screen text-white">

    <!-- Navbar -->
    <nav class="bg-green-900/90 shadow-md backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('plots.index') }}" class="text-white text-2xl font-bold tracking-wide">
                    EternaLink
                </a>

                <!-- Links and User -->
                <div class="flex items-center gap-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-200 hover:text-white transition text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('plots.index') }}" class="text-gray-200 hover:text-white transition text-sm font-medium">
                            Cemetery
                        </a>

                        <!-- User Dropdown -->
                        <div class="relative dropdown">
                            <button class="flex items-center gap-2 text-sm text-gray-200 hover:text-white focus:outline-none">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-44 bg-white rounded-md shadow-lg py-2 z-50 dropdown-menu hidden text-sm text-gray-800">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-gray-100">Edit Profile</a>
                                <form method="POST" action="{{ route('logout') }}" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:text-white text-sm">Login</a>
                        <a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto mt-10 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <!-- Optional: Footer -->
    <footer class="text-center mt-16 text-sm text-gray-400 py-6">
        &copy; {{ date('Y') }} EternaLink – Cemetery Management
    </footer>
</body>
</html>
