<x-app-layout>
    <div class="max-w-2xl mx-auto bg-gray-800 p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-semibold mb-4">Enable Two-Factor Authentication</h2>
        <p class="text-sm text-gray-300 mb-2">
            Scan the QR code with your Google Authenticator app or use this key:
            <strong class="text-white">{{ $secret }}</strong>
        </p>
        <img class="my-4 w-40" src="data:image/svg+xml;base64,{{ $qrCodeSvg }}" alt="QR Code">

        <form method="POST" action="{{ route('2fa.enable') }}">
            @csrf
            <div class="mb-4">
                <label for="otp" class="block text-sm font-medium text-gray-300">Enter OTP:</label>
                <input type="number" name="otp" id="otp" required class="mt-1 block w-full rounded bg-gray-700 border border-gray-600 text-white p-2">
                @error('otp')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
                @if (session('error') && !$errors->has('otp'))
                    <p class="text-red-400 text-sm mt-1">{{ session('error') }}</p>
                @endif
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white font-semibold" type="submit">
                Enable 2FA
            </button>
        </form>
    </div>
</x-app-layout>
