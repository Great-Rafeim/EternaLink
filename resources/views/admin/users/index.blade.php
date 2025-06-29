<x-admin-layout>
    <div class="container py-4">
        <h1 class="mb-4 fw-bold" style="color: #1565c0;">
            <i class="bi bi-people-fill me-2"></i> User Management
        </h1>

        <div class="row g-4">
            {{-- Pending Registration Requests --}}
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-gradient bg-primary text-white d-flex align-items-center">
                        <i class="bi bi-person-check-fill me-2"></i>
                        <span class="fw-semibold">Pending Registration Requests</span>
                        <span class="badge bg-danger ms-2">{{ $pendingRequests->count() ?? 0 }}</span>
                    </div>
                    <div class="card-body p-0">
                        @if($pendingRequests->count())
                            <ul class="list-group list-group-flush">
                                @foreach($pendingRequests as $request)
                                    <li class="list-group-item d-flex align-items-center">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $request->name }}
                                                <span class="badge bg-secondary text-capitalize ms-2">{{ $request->role }}</span>
                                            </div>
                                            <div class="small text-muted">{{ $request->email }}</div>
                                            <div class="small text-muted">Applied: {{ $request->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div class="ms-auto d-flex gap-2">
                                            <a href="{{ route('admin.users.show', $request->id) }}" class="btn btn-outline-primary btn-sm" title="Review">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-person-dash display-6"></i>
                                <p class="mb-0">No pending registration requests at the moment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Main User Management --}}
            <div class="col-lg-8">
                {{-- Filters and Search --}}
{{-- Filters and Search --}}
<form id="user-filter-form" method="GET" action="{{ route('admin.users.index') }}" class="mb-4">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <select name="role" id="filter-role" class="form-select">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="client" {{ request('role') == 'client' ? 'selected' : '' }}>Client</option>
                <option value="agent" {{ request('role') == 'agent' ? 'selected' : '' }}>Agent</option>
                <option value="funeral" {{ request('role') == 'funeral' ? 'selected' : '' }}>Funeral</option>
                <option value="cemetery" {{ request('role') == 'cemetery' ? 'selected' : '' }}>Cemetery</option>
            </select>
        </div>
        <div class="col-auto">
            <select name="status" id="filter-status" class="form-select">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col">
            <input type="text" name="search" id="filter-search" value="{{ request('search') }}" class="form-control" placeholder="Search name or email...">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i> Filter
            </button>
        </div>
    </div>
</form>


                {{-- Flash Message --}}
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                {{-- Create and Export Buttons --}}
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-success">
                        <i class="bi bi-plus-lg me-1"></i> Create New User
                    </a>
                    <a href="{{ route('admin.users.export') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-download me-1"></i> Export to CSV
                    </a>
                </div>

                {{-- User Table --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td class="text-capitalize">{{ $user->role }}</td>
                                    <td>
                                        @if($user->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($user->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($user->status === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-light text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>
                                        <div class="dropdown text-center">
                                            <button class="btn btn-link p-0" type="button" id="actionDropdown{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical fs-5"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown{{ $user->id }}">
                                                <li>
                                                    <a href="{{ route('admin.users.show', $user->id) }}" class="dropdown-item">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="dropdown-item">
                                                        <i class="bi bi-pencil me-1"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            <i class="bi bi-trash me-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $users->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('user-filter-form');
    const tbody = document.getElementById('user-table-body');
    let timer;

    function fetchFilteredUsers() {
        const params = new URLSearchParams(new FormData(form)).toString();
        fetch(`{{ route('admin.users.index') }}/ajax-search?${params}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                tbody.innerHTML = html;
            })
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to fetch users.</td></tr>`;
            });
    }

    // Debounce for search
    document.getElementById('filter-search').addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(fetchFilteredUsers, 300);
    });

    // Change triggers for role/status
    document.getElementById('filter-role').addEventListener('change', fetchFilteredUsers);
    document.getElementById('filter-status').addEventListener('change', fetchFilteredUsers);

    // Optional: Fallback for manual submit (full page reload)
    form.addEventListener('submit', function(e) {
        // If you want AJAX on submit too, uncomment:
        // e.preventDefault();
        // fetchFilteredUsers();
    });
});
</script>
@endpush

    </script>
    @endpush
</x-admin-layout>
