<x-app-layout>
    <x-authentication-card>
        <x-slot name="logo" />
        
        {{-- Show validation errors if the code is wrong --}}
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf

            {{-- 6-digit TOTP code field --}}
            <div>
                <x-label for="code" value="{{ __('Authentication Code') }}" />
                <x-input id="code"
                         name="code"
                         type="text"
                         inputmode="numeric"
                         autofocus
                         autocomplete="one-time-code"
                         class="mt-1 block w-full" />
            </div>

            {{-- Recovery code field (optional) --}}
            <div class="mt-4">
                <x-label for="recovery_code" value="{{ __('Recovery Code') }}" />
                <x-input id="recovery_code"
                         name="recovery_code"
                         type="text"
                         autocomplete="one-time-code"
                         class="mt-1 block w-full" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button>
                    {{ __('Login') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-app-layout>
