<x-app-layout>
    <div class="flex justify-center mt-12 px-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">

            @if(session('force_password'))
                <div class="mb-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200 rounded">
                    <strong>Notice:</strong> {{ session('force_password') }}
                </div>
            @endif

            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-6">Change Your Password</h2>

            <form method="POST" action="{{ route('password.change.update') }}">
                @csrf

                <!-- New Password -->
                <div class="mb-4">
                    <x-input-label for="password" :value="__('New Password')" />
                    <x-text-input id="password" name="password" type="password"
                                  class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                  class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>
                        {{ __('Update Password') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
