<x-app-layout>
    <div class="max-w-md mx-auto mt-10 bg-gray-800 p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-white">Two-Factor Authentication</h2>
        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <p class="text-sm text-gray-300 mb-2">Enter the 6-digit code from your authenticator app.</p>

            <div class="mb-4">
                <label for="one_time_password" class="block text-sm text-gray-300">OTP Code:</label>
                <input type="number" name="one_time_password" required
                    class="mt-1 w-full p-2 bg-gray-700 border border-gray-600 text-white rounded" autofocus>
            </div>

            @foreach ($errors->all() as $error)
                <p class="text-red-400 text-sm">{{ $error }}</p>
            @endforeach

            <button class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white font-semibold">
                Verify
            </button>
        </form>
    </div>
</x-app-layout>
