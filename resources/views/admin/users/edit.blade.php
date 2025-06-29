<x-admin-layout>
    <div class="container">
        <h2 class="mb-4 fw-bold" style="color: #1565c0;">
            <i class="bi bi-pencil-square me-2"></i> Edit User
        </h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm rounded-4 border-0 mb-5">
            <div class="card-body">
                <form action="{{ route('admin.users.update', $user->id) }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label fw-semibold">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="client" {{ $user->role == 'client' ? 'selected' : '' }}>Client</option>
                            <option value="funeral" {{ $user->role == 'funeral' ? 'selected' : '' }}>Funeral Parlor</option>
                            <option value="cemetery" {{ $user->role == 'cemetery' ? 'selected' : '' }}>Cemetery</option>
                            <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle me-1"></i> Update User
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
