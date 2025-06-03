<x-app-layout>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-gray-900/90 backdrop-blur-md p-8 rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-center text-2xl font-bold text-white">
                Reset Your Password
            </h2>
            <p class="text-center text-sm text-gray-400">
                Enter your new password to continue.
            </p>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="mt-1 w-full" type="email" name="email"
                                  :value="old('email', $request->email)" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- New Password -->
                <div>
                    <x-input-label for="password" :value="__('New Password')" />
                    <x-text-input id="password" class="mt-1 w-full" type="password"
                                  name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                    <x-text-input id="password_confirmation" class="mt-1 w-full" type="password"
                                  name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-4">
                    <x-primary-button class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-5 rounded-lg transition">
                        {{ __('Reset Password') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
