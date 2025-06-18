<x-layouts.funeral>
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-white">Inventory Categories</h1>
            <a href="{{ route('funeral.categories.create') }}" class="btn btn-success shadow">
                <i class="bi bi-plus-circle me-1"></i> Add Category
            </a>
        </div>
        <div class="table-responsive bg-dark rounded shadow">
            <table class="table table-dark table-hover table-bordered align-middle mb-0">
                <thead class="table-secondary text-dark">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>
                        <th scope="col">Type</th>
                        <th scope="col" class="text-center">Actions</th>
                        
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
                            <td colspan="3" class="text-center text-muted">No categories found.</td>
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
