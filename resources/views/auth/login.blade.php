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

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm mb-1">Password</label>
                    <input id="password" type="password" name="password" required
                           class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500" />
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
