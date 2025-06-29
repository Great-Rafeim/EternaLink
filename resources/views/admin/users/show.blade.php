<x-admin-layout>
    <div class="container py-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-link mb-3">← Back to Dashboard</a>

        <div class="card shadow border-0 rounded-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i>
                    Review User Registration
                </h4>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9 fw-semibold">{{ $user->name }}</dd>

                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $user->email }}</dd>

                    <dt class="col-sm-3">Role</dt>
                    <dd class="col-sm-9 text-capitalize">
                        <span class="badge bg-secondary">{{ $user->role }}</span>
                    </dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">
                        @if($user->status == 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($user->status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($user->status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @else
                            <span class="badge bg-light text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Registered At</dt>
                    <dd class="col-sm-9">{{ $user->created_at->format('Y-m-d H:i:s') }}</dd>
                </dl>

                {{-- DOCUMENTS --}}
                <div class="mt-5">
                    <h5 class="fw-semibold mb-3"><i class="bi bi-folder2-open me-1 text-primary"></i> Submitted Documents</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="fw-semibold mb-2">Proof of Ownership</div>
                                @if($user->proof_of_ownership)
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ asset('storage/' . $user->proof_of_ownership) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-file-earmark-check me-1"></i> Preview
                                        </a>
                                        <a href="{{ asset('storage/' . $user->proof_of_ownership) }}" download class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ basename($user->proof_of_ownership) }}
                                    </div>
                                @else
                                    <span class="text-muted">No file submitted.</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="fw-semibold mb-2">Government ID</div>
                                @if($user->government_id)
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ asset('storage/' . $user->government_id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-file-earmark-person me-1"></i> Preview
                                        </a>
                                        <a href="{{ asset('storage/' . $user->government_id) }}" download class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ basename($user->government_id) }}
                                    </div>
                                @else
                                    <span class="text-muted">No file submitted.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                @if($user->status === 'pending')
                    <form method="POST" action="{{ route('admin.users.approve', $user->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check2"></i> Approve
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.reject', $user->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this user?');">
                            <i class="bi bi-x"></i> Reject
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary ms-auto">Back to List</a>
            </div>
        </div>
    </div>
</x-admin-layout>
