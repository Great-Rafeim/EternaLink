<x-app-layout>
    <div class="max-w-2xl mx-auto bg-gray-800 p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-semibold mb-4">Disable Two-Factor Authentication</h2>
        <form method="POST" action="{{ route('2fa.disable') }}">
            @csrf
            <p class="text-sm text-gray-300 mb-2">
                Enter the current OTP to disable 2FA. This will remove your authentication key.
            </p>

            <div class="mb-4">
                <label for="otp" class="block text-sm font-medium text-gray-300">Enter OTP:</label>
                <input type="number" name="otp" required class="mt-1 block w-full rounded bg-gray-700 border border-gray-600 text-white p-2">
            </div>

            @foreach ($errors->all() as $error)
                <p class="text-red-400 text-sm">{{ $error }}</p>
            @endforeach

            <button class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-white font-semibold" type="submit">
                Disable 2FA
            </button>
        </form>
    </div>
</x-app-layout>
