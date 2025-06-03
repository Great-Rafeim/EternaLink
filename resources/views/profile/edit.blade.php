<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Two Factor Authentication Settings -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-white">Two-Factor Authentication</h2>
                    <p class="mt-1 text-sm text-gray-400">
                        Manage your two-factor authentication settings with Google Authenticator.
                    </p>

                    @if (session('success'))
                        <p class="mt-2 text-green-400 text-sm">
                            {{ session('success') }}
                        </p>
                    @elseif (session('error'))
                        <p class="mt-2 text-red-400 text-sm">
                            {{ session('error') }}
                        </p>
                    @elseif (session('info'))
                        <p class="mt-2 text-yellow-400 text-sm">
                            {{ session('info') }}
                        </p>
                    @endif

                    @if (auth()->user()->google2fa_secret)
                        <!-- Disable 2FA -->
                        <a href="{{ route('2fa.disable.form') }}">
                            <x-primary-button class="mt-4">
                                {{ __('Disable Two-Factor Authentication') }}
                            </x-primary-button>
                        </a>
                    @else
                        <!-- Enable 2FA -->
                        <form action="{{ route('2fa.setup') }}" method="GET">
                            <x-primary-button class="mt-4">
                                {{ __('Enable Two-Factor Authentication') }}
                            </x-primary-button>
                        </form>
                    @endif
                </div>
            </div>


            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
