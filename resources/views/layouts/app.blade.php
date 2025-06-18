<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? config('app.name', 'EternaLink') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('head')
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black min-h-screen text-white font-[Poppins]">

    <!-- Navbar -->
    <nav class="bg-gray-900 bg-opacity-90 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="{{ url('/') }}" class="text-2xl font-bold text-white">EternaLink</a>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-300 hover:text-white text-sm font-medium transition">Dashboard</a>
                        <!-- Profile dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center text-gray-300 hover:text-white focus:outline-none text-sm font-medium">
                                <svg class="w-6 h-6 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5.121 17.804A13.937 13.937 0 0112 15c2.28 0 4.417.522 6.293 1.447M15 21v-2a4 4 0 00-8 0v2m6-4V7a4 4 0 00-8 0v10"></path>
                                </svg>
                                {{ auth()->user()->name ?? 'Account' }}
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-40 bg-gray-800 rounded-md shadow-lg py-2 z-50">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-200 hover:bg-gray-700">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-red-400 hover:bg-gray-700">Logout</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:text-white text-sm font-medium">Login</a>
                        <a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm font-medium">Register</a>
                    @endauth
                </div>
                <!-- Hamburger for mobile -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-400 hover:text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden px-4 pb-4">
            @auth
                <a href="{{ route('dashboard') }}" class="block text-gray-300 py-2 hover:text-white">Dashboard</a>
                <a href="{{ route('profile.edit') }}" class="block text-gray-300 py-2 hover:text-white">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left text-red-400 py-2 hover:bg-gray-800">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block text-gray-300 py-2 hover:text-white">Login</a>
                <a href="{{ route('register') }}" class="block text-gray-300 py-2 hover:text-white">Register</a>
            @endauth
        </div>
    </nav>

    <!-- Page Heading (optional slot) -->
    @isset($header)
        <header class="py-6 border-b border-gray-700 mb-8">
            <div class="max-w-7xl mx-auto px-4">
                <h1 class="text-2xl font-semibold text-white">{{ $header }}</h1>
            </div>
        </header>
    @endisset

    <!-- Flash / Toast messages -->
    @if (session('success'))
        <div class="fixed top-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2 animate-fade-in">
            <svg class="w-5 h-5" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="fixed top-5 right-5 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2 animate-fade-in">
            <svg class="w-5 h-5" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Page Content -->
    <main class="py-10 px-4 max-w-7xl mx-auto">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="text-center py-4 text-gray-500 text-sm border-t border-gray-800 mt-16">
        &copy; {{ date('Y') }} EternaLink. All rights reserved.
    </footer>

    <!-- AlpineJS for dropdown -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('mobile-menu-btn').onclick = function() {
                let menu = document.getElementById('mobile-menu');
                menu.classList.toggle('hidden');
            }
        });
    </script>
    <style>
        .animate-fade-in {
            animation: fade-in 0.5s;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-20px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</body>
</html>
