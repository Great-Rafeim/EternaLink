<x-app-layout>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-gray-900/90 backdrop-blur-md p-8 rounded-2xl shadow-xl space-y-6 text-white">
            <h2 class="text-2xl font-bold text-center">
                Email Verification Required
            </h2>

            <p class="text-sm text-gray-300 text-center leading-relaxed">
                Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you. If you didn't receive the email, you can request another.
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="text-sm font-medium text-green-400 text-center">
                    A new verification link has been sent to your email.
                </div>
            @endif

            <div class="flex flex-col sm:flex-row justify-between gap-4 pt-4">
                <!-- Resend Link -->
                <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                    @csrf
                    <x-primary-button class="w-full justify-center">
                        {{ __('Resend Verification Email') }}
                    </x-primary-button>
                </form>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit"
                            class="w-full text-center underline text-sm text-gray-400 hover:text-white transition duration-150 ease-in-out">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
