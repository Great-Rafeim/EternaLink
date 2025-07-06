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
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <strong>
                    <i class="bi bi-journal-check me-1"></i> Recent Logins
                </strong>
                <input id="login-search" class="form-control form-control-sm w-auto" placeholder="Search..." autocomplete="off" style="max-width:200px;">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="login-table">
                        <thead class="table-light">
                            <tr>
                                <th class="sort" data-sort="user_name" style="cursor:pointer">Name <i class="bi bi-caret-down-fill small"></i></th>
                                <th class="sort" data-sort="user_email" style="cursor:pointer">Email <i class="bi bi-caret-down-fill small"></i></th>
                                <th class="sort" data-sort="user_role" style="cursor:pointer">Role <i class="bi bi-caret-down-fill small"></i></th>
                                <th class="sort" data-sort="login_time" style="cursor:pointer">Login Time <i class="bi bi-caret-down-fill small"></i></th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @forelse($logins as $login)
                                <tr>
                                    <td class="user_name">{{ $login->user->name }}</td>
                                    <td class="user_email">{{ $login->user->email }}</td>
                                    <td class="user_role text-capitalize">{{ $login->user->role }}</td>
                                    <td class="login_time">{{ $login->created_at->format('Y-m-d H:i:s') }}</td>
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

    {{-- List.js for client-side search & sorting --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
    <script>
        // Init List.js for this page's table (only on current page)
        const loginList = new List('login-table', {
            valueNames: [
                'user_name', 'user_email', 'user_role', 'login_time'
            ],
            listClass: 'list'
        });
        document.getElementById('login-search').addEventListener('keyup', function() {
            loginList.search(this.value);
        });
    </script>
</x-admin-layout>
