<x-admin-layout>
    <div class="container">
        <h1 class="mb-4 fw-bold" style="color: #1565c0;">
            <i class="bi bi-person-plus me-2"></i> Create New User
        </h1>

        {{-- Back Button --}}
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to User List
        </a>

        {{-- Create User Form --}}
        <div class="card shadow-sm rounded-4 border-0 mb-5">
            <div class="card-body">
                <form action="{{ route('admin.users.store') }}" method="POST" autocomplete="off">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required value="{{ old('name') }}">
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required value="{{ old('email') }}">
                        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label fw-semibold">User Role</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="">Select role</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="client" {{ old('role') === 'client' ? 'selected' : '' }}>Client</option>
                            <option value="funeral" {{ old('role') === 'funeral' ? 'selected' : '' }}>Funeral Parlor</option>
                            <option value="cemetery" {{ old('role') === 'cemetery' ? 'selected' : '' }}>Cemetery</option>
                        </select>
                        @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Initial Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle me-1"></i> Create User
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
