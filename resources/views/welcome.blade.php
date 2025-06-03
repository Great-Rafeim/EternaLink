<x-app-layout>
    <main class="flex flex-col items-center justify-center h-[80vh] text-center px-4">
        <h1 class="text-6xl font-bold mb-10">Welcome to EternaLink</h1>

        @auth
            <a href="{{ route('dashboard') }}" 
               class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg text-white text-lg font-semibold transition">
                Go to Dashboard
            </a>
        @else
            <p class="text-gray-400 text-lg max-w-xl mb-6">
                Please login or register to access your dashboard and services.
            </p>

            <div class="flex gap-4">
                <a href="{{ route('login') }}" 
                   class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg text-white text-sm font-medium transition">
                    Login
                </a>
                <a href="{{ route('register') }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 px-6 py-2 rounded-lg text-white text-sm font-medium transition">
                    Register
                </a>
            </div>
        @endauth
    </main>
</x-app-layout>
