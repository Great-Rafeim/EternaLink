<x-app-layout>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-gray-900/95 backdrop-blur-lg p-8 rounded-3xl shadow-2xl space-y-8 border border-gray-800">
            <div class="space-y-1">
                <h2 class="text-center text-3xl font-extrabold text-white tracking-tight">
                    Create your account
                </h2>
                <p class="text-center text-base text-gray-400">
                    Join EternaLink and get started.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-6" id="registration-form">
                @csrf

                <!-- Name -->
                <div>
                    <x-input-label for="name" value="Name *" />
                    <x-text-input id="name" class="mt-1 w-full" type="text" name="name"
                                  :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <x-input-label for="email" value="Email *" />
                    <x-text-input id="email" class="mt-1 w-full" type="email" name="email"
                                  :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Register As *</label>
                    <div class="flex flex-col gap-2">
                        @foreach (['client' => 'Client', 'funeral' => 'Funeral Parlor', 'cemetery' => 'Cemetery'] as $value => $label)
                            <label class="inline-flex items-center text-gray-200">
                                <input type="radio" name="role" value="{{ $value }}"
                                       {{ old('role') === $value ? 'checked' : '' }}
                                       class="form-radio text-blue-500 focus:ring focus:ring-blue-300"
                                       required
                                       @if($value !== 'client') data-needs-proof="1" @endif>
                                <span class="ml-2">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('role')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Conditional Business Documents Upload -->
                <div id="business-proof-fields" class="{{ old('role') === 'funeral' || old('role') === 'cemetery' ? '' : 'hidden' }} space-y-3">
                    <div>
                        <x-input-label for="proof_of_ownership" value="Proof of Business Ownership *" />
                        <input id="proof_of_ownership" name="proof_of_ownership" type="file" accept="image/*,application/pdf"
                               class="mt-1 block w-full border-gray-700 text-gray-200 bg-gray-900 file:bg-blue-700 file:text-white file:rounded-lg file:px-3 file:py-1 file:mr-3 file:border-none rounded-lg" />
                        <x-input-error :messages="$errors->get('proof_of_ownership')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="government_id" value="Government Issued ID *" />
                        <input id="government_id" name="government_id" type="file" accept="image/*,application/pdf"
                               class="mt-1 block w-full border-gray-700 text-gray-200 bg-gray-900 file:bg-blue-700 file:text-white file:rounded-lg file:px-3 file:py-1 file:mr-3 file:border-none rounded-lg" />
                        <x-input-error :messages="$errors->get('government_id')" class="mt-2" />
                    </div>
                </div>

                <!-- Password with Show/Hide Button -->
                <div x-data="{ show: false }" class="relative">
                    <x-input-label for="password" value="Password *" />
                    <input :type="show ? 'text' : 'password'" id="password" name="password" required
                           autocomplete="new-password"
                           class="mt-1 w-full pr-12 rounded-lg border-gray-700 text-gray-200 bg-gray-900"
                           x-ref="password" />
                    <button type="button"
                        tabindex="-1"
                        @click="show = !show"
                        class="absolute right-3 top-9 text-gray-400 hover:text-blue-400 focus:outline-none"
                        aria-label="Toggle Password Visibility">
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-7 9-9 9s-9-4-9-9 7-9 9-9 9 4 9 9z" />
                        </svg>
                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-9-9 0-1.43.31-2.79.875-3.995M15 12a3 3 0 01-4.785 2.418M9.879 9.879A3 3 0 0115 12m2.121 2.121a3 3 0 010-4.242M20.117 20.117A10.05 10.05 0 0012 19c-5 0-9-4-9-9" />
                        </svg>
                    </button>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password with Show/Hide Button -->
                <div x-data="{ show: false }" class="relative">
                    <x-input-label for="password_confirmation" value="Confirm Password *" />
                    <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                           autocomplete="new-password"
                           class="mt-1 w-full pr-12 rounded-lg border-gray-700 text-gray-200 bg-gray-900"
                           x-ref="password_confirmation" />
                    <button type="button"
                        tabindex="-1"
                        @click="show = !show"
                        class="absolute right-3 top-9 text-gray-400 hover:text-blue-400 focus:outline-none"
                        aria-label="Toggle Password Visibility">
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-7 9-9 9s-9-4-9-9 7-9 9-9 9 4 9 9z" />
                        </svg>
                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-9-9 0-1.43.31-2.79.875-3.995M15 12a3 3 0 01-4.785 2.418M9.879 9.879A3 3 0 0115 12m2.121 2.121a3 3 0 010-4.242M20.117 20.117A10.05 10.05 0 0012 19c-5 0-9-4-9-9" />
                        </svg>
                    </button>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-between pt-2">
                    <a class="text-sm text-gray-400 hover:text-gray-200 transition" href="{{ route('login') }}">
                        Already registered?
                    </a>
                    <x-primary-button class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg font-semibold transition">
                        Register
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Alpine.js for dynamic form --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleRadios = document.querySelectorAll('input[name="role"]');
            const proofFields = document.getElementById('business-proof-fields');
            function toggleProofFields() {
                let needsProof = false;
                roleRadios.forEach(radio => {
                    if (radio.checked && radio.dataset.needsProof) {
                        needsProof = true;
                    }
                });
                proofFields.classList.toggle('hidden', !needsProof);
                // Set required property dynamically
                document.getElementById('proof_of_ownership').required = needsProof;
                document.getElementById('government_id').required = needsProof;
            }
            roleRadios.forEach(radio => radio.addEventListener('change', toggleProofFields));
            toggleProofFields(); // on load
        });
    </script>

    {{-- Modal for pending registration --}}
    <div
        x-data="{ open: {{ session('status') ? 'true' : 'false' }} }"
        x-show="open"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70"
    >
        <div class="bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center border border-blue-600">
            <svg class="mx-auto mb-4 h-12 w-12 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"></path>
                <circle cx="12" cy="12" r="10"></circle>
            </svg>
            <h3 class="text-2xl font-bold mb-2 text-white">Thank you for registering!</h3>
            <p class="mb-6 text-gray-300">Please wait for the administrator to review and approve your application.<br>
            You will be notified through email once your account is approved.</p>
            <button
                @click="open = false"
                class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition focus:outline-none"
            >Close</button>
        </div>
    </div>
</x-app-layout>
