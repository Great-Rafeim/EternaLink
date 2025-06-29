<x-admin-layout>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: #1565c0;">
                <i class="bi bi-clock-history me-2"></i> Full Login History
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="card shadow-sm rounded-4 border-0">
            <div class="card-header bg-light">
                <strong>
                    <i class="bi bi-journal-check me-1"></i> Recent Logins
                </strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Login Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logins as $login)
                                <tr>
                                    <td>{{ $login->user->name }}</td>
                                    <td>{{ $login->user->email }}</td>
                                    <td class="text-capitalize">{{ $login->user->role }}</td>
                                    <td>{{ $login->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No login records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-light">
                {{ $logins->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-admin-layout>
