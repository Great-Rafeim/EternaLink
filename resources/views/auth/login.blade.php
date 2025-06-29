<x-app-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white">
        <div class="w-full max-w-md p-8 space-y-6 bg-gray-800 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-center">Login to EternaLink</h2>

            @if(session('status'))
                <div class="bg-green-600 text-white px-4 py-2 rounded text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm mb-1">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500" />
                    @error('email')
                        <span class="text-red-400 text-sm">{{ $message }}</span>
                    @enderror
                </div>
<!-- Password with Show/Hide Button -->
<div x-data="{ show: false }" class="relative">
    <label for="password" class="block text-sm mb-1">Password</label>
    <input :type="show ? 'text' : 'password'"
           id="password"
           name="password"
           required
           class="w-full px-4 py-2 pr-12 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500" />
    <button type="button"
            @click="show = !show"
            tabindex="-1"
            class="absolute right-3 top-8 text-gray-400 hover:text-yellow-400 focus:outline-none"
            aria-label="Toggle Password Visibility">
        <!-- Eye Closed -->
        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-7 9-9 9s-9-4-9-9 7-9 9-9 9 4 9 9z" />
        </svg>
        <!-- Eye Open -->
        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-9-9 0-1.43.31-2.79.875-3.995M15 12a3 3 0 01-4.785 2.418M9.879 9.879A3 3 0 0115 12m2.121 2.121a3 3 0 010-4.242M20.117 20.117A10.05 10.05 0 0012 19c-5 0-9-4-9-9" />
        </svg>
    </button>
    @error('password')
        <span class="text-red-400 text-sm">{{ $message }}</span>
    @enderror
</div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="rounded border-gray-600 text-yellow-500 shadow-sm focus:ring-yellow-400">
                    <label for="remember_me" class="ml-2 text-sm">Remember Me</label>
                </div>

                <!-- Login Button -->
                <div>
                    <button type="submit"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-semibold py-2 rounded shadow transition">
                        Log In
                    </button>
                </div>

                <!-- Forgot Password -->
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="text-sm text-yellow-400 hover:underline">
                        Forgot your password?
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
