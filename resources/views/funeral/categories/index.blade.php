@push('styles')
<style>
    .sort-caret { font-size: 1em; margin-left: 2px; vertical-align: middle; }
    .sort-caret.inactive { opacity: 0.3; }
</style>
@endpush

<x-layouts.funeral>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-white">Inventory Categories</h1>
            <a href="{{ route('funeral.categories.create') }}" class="btn btn-success shadow">
                <i class="bi bi-plus-circle me-1"></i> Add Category
            </a>
        </div>

        {{-- Filter and Search Bar --}}
        <form method="GET" class="mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="🔍 Search name or description"
                           value="{{ old('search', $search) }}">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="asset" {{ $type === 'asset' ? 'selected' : '' }}>Bookable Asset</option>
                        <option value="consumable" {{ $type === 'consumable' ? 'selected' : '' }}>Consumable</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('funeral.categories.index') }}" class="btn btn-secondary w-100">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <div class="table-responsive bg-dark rounded shadow">
            <table class="table table-dark table-hover table-bordered align-middle mb-0">
                <thead class="table-secondary text-dark">
                    @php
                        function render_sort_caret_cat($col, $sort, $direction) {
                            $asc = 'sort-caret bi bi-caret-up-fill ' . ($sort === $col && $direction === 'asc' ? '' : 'inactive');
                            $desc = 'sort-caret bi bi-caret-down-fill ' . ($sort === $col && $direction === 'desc' ? '' : 'inactive');
                            return "<i class='$asc'></i><i class='$desc'></i>";
                        }
                    @endphp
                    <tr>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort' => 'name',
                                'direction' => ($sort === 'name' && $direction === 'asc') ? 'desc' : 'asc'
                            ]) }}" class="text-dark text-decoration-none">
                                Name {!! render_sort_caret_cat('name', $sort, $direction) !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort' => 'description',
                                'direction' => ($sort === 'description' && $direction === 'asc') ? 'desc' : 'asc'
                            ]) }}" class="text-dark text-decoration-none">
                                Description {!! render_sort_caret_cat('description', $sort, $direction) !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort' => 'is_asset',
                                'direction' => ($sort === 'is_asset' && $direction === 'asc') ? 'desc' : 'asc'
                            ]) }}" class="text-dark text-decoration-none">
                                Type {!! render_sort_caret_cat('is_asset', $sort, $direction) !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort' => 'reservation_mode',
                                'direction' => ($sort === 'reservation_mode' && $direction === 'asc') ? 'desc' : 'asc'
                            ]) }}" class="text-dark text-decoration-none">
                                Reservation Mode {!! render_sort_caret_cat('reservation_mode', $sort, $direction) !!}
                            </a>
                        </th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td>{{ $category->description ?? '-' }}</td>
                            <td>
                                @if($category->is_asset)
                                    <span class="badge bg-info text-dark">Bookable Asset</span>
                                @else
                                    <span class="badge bg-secondary">Consumable</span>
                                @endif
                            </td>
                            <td>
                                @if($category->is_asset)
                                    @if($category->reservation_mode === 'continuous')
                                        <span class="badge bg-primary">Continuous</span>
                                        <span class="text-muted small ms-1">(multi-day use)</span>
                                    @elseif($category->reservation_mode === 'single_event')
                                        <span class="badge bg-warning text-dark">Single Event</span>
                                        <span class="text-muted small ms-1">(by date/time)</span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('funeral.categories.edit', $category) }}" class="btn btn-sm btn-primary me-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('funeral.categories.destroy', $category) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $categories->links() }}
        </div>
    </div>
</x-layouts.funeral>
