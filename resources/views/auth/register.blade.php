<x-app-layout>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-gray-900/90 backdrop-blur-md p-8 rounded-2xl shadow-2xl space-y-6">
            <h2 class="text-center text-3xl font-bold text-white">
                Create your account
            </h2>
            <p class="text-center text-sm text-gray-400">
                Join EternaLink and get started.
            </p>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" class="mt-1 w-full" type="text" name="name"
                                  :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="mt-1 w-full" type="email" name="email"
                                  :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Register As</label>
                    <div class="flex flex-col gap-2">
                        @foreach (['client' => 'Client', 'funeral' => 'Funeral Parlor', 'cemetery' => 'Cemetery'] as $value => $label)
                            <label class="inline-flex items-center text-gray-200">
                                <input type="radio" name="role" value="{{ $value }}"
                                       {{ old('role') === $value ? 'checked' : '' }}
                                       class="form-radio text-blue-500 focus:ring focus:ring-blue-300">
                                <span class="ml-2">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('role')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="mt-1 w-full" type="password"
                                  name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="mt-1 w-full" type="password"
                                  name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-between pt-2">
                    <a class="text-sm text-gray-400 hover:text-gray-200 transition" href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>

                    <x-primary-button class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-5 rounded-lg transition">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
