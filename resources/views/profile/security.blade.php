@extends('profile.settings')

@section('content')
    <h2 class="text-2xl font-bold mb-4">Two-Factor Authentication</h2>

    @if (session('status') === 'two-factor-authentication-disabled')
        <div class="text-green-400 mb-4">Two-factor authentication has been disabled.</div>
    @elseif (session('status') === 'two-factor-authentication-enabled')
        <div class="text-green-400 mb-4">Two-factor authentication has been enabled.</div>
    @endif

    @if (! auth()->user()->two_factor_secret)
        <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
            @csrf
            <x-primary-button>Enable Two-Factor Authentication</x-primary-button>
        </form>
    @else
        <div class="mb-4">
            <p class="text-sm">Two-factor authentication is currently <strong>enabled</strong>.</p>

            <div class="mt-4">
                <p class="text-sm font-medium">Scan this QR code with your Authenticator App:</p>
                <div class="mt-2 bg-white p-4 inline-block">
                    {!! auth()->user()->twoFactorQrCodeSvg() !!}
                </div>
            </div>

            @if (session('status') !== 'two-factor-authentication-confirmed')
                <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="mt-4">
                    @csrf
                    <label for="code" class="block text-sm font-medium text-gray-300">Enter the 6-digit code</label>
                    <input type="text" name="code" id="code" class="mt-1 block w-full text-black rounded" required>
                    <x-primary-button class="mt-3">Confirm</x-primary-button>
                </form>
            @endif

            <form method="POST" action="{{ url('/user/two-factor-authentication') }}" class="mt-6">
                @csrf
                @method('DELETE')
                <x-danger-button>Disable Two-Factor Authentication</x-danger-button>
            </form>
        </div>
    @endif
@endsection
