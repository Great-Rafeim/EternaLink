<x-cemetery-layout>
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <h1 class="h3 fw-bold mb-0 text-white">Cemetery Plots</h1>
        <a href="{{ route('cemetery.plots.create') }}"
           class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-2"></i> Add New Plot
        </a>
    </div>

    {{-- Success message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter/Search Form --}}
    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-12 col-md-4 col-lg-3">
            <label for="search" class="form-label text-secondary mb-1">Search</label>
            <input type="text" name="search" id="search"
                   value="{{ request('search') }}"
                   placeholder="Plot #, owner, or deceased"
                   class="form-control form-control-sm" />
        </div>
        <div class="col-12 col-md-3 col-lg-2">
            <label for="status" class="form-label text-secondary mb-1">Status</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
            </select>
        </div>
        <div class="col-6 col-md-2 col-lg-2 d-grid">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
        </div>
        <div class="col-6 col-md-2 col-lg-2 d-grid">
            <a href="{{ route('cemetery.plots.index') }}" class="btn btn-outline-light btn-sm">
                <i class="bi bi-x-circle me-1"></i> Clear
            </a>
        </div>
    </form>

    {{-- Table of Plots --}}
    <div class="card bg-dark border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Plot #</th>
                        <th>Section</th>
                        <th>Block</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th style="width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($plots as $plot)
                    <tr>
                        <td>{{ $plot->plot_number }}</td>
                        <td>{{ $plot->section }}</td>
                        <td>{{ $plot->block }}</td>
                        <td>{{ ucfirst($plot->type) }}</td>
                        <td>
                            @if($plot->status === 'available')
                                <span class="badge bg-secondary">Available</span>
                            @elseif($plot->status === 'reserved')
                                <span class="badge bg-warning text-dark">Reserved</span>
                            @else
                                <span class="badge bg-danger">Occupied</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('cemetery.plots.edit', $plot) }}"
                                   class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-pencil-square"></i> Update
                                </a>
                                <form action="{{ route('cemetery.plots.destroy', $plot) }}" method="POST"
                                      onsubmit="return confirm('Delete this plot?')" class="m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">No plots found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $plots->links() }}
    </div>
</x-cemetery-layout>
