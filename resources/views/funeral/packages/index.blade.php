<x-layouts.funeral>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white">Created Funeral Service Packages</h2>
    </div>

    <div class="mb-4">
        <a href="{{ route('packages.create') }}" class="btn btn-success">+ Create Package</a>
    </div>

    @if($packages->isEmpty())
        <p class="text-muted">No packages created yet.</p>
    @else
    <div class="row row-cols-1 row-cols-md-1 row-cols-lg-2 g-4 package-card">
        @foreach($packages as $package)
            <div class="col">
                <div class="card h-100 bg-dark text-white shadow-lg border-0">
                    <div class="card-body">
                        <h4 class="card-title">{{ $package->name }}</h4>
                        <p class="card-text">{{ $package->description ?: 'No description provided.' }}</p>
                    </div>
                    <div class="card-footer bg-dark border-top border-secondary">
                        <p class="mb-2"><strong>Total Price:</strong> ₱{{ number_format($package->total_price, 2) }}</p>

                        <div class="d-flex justify-content-center align-items-end gap-2">
                            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#viewPackageModal{{ $package->id }}">View</button>
                            <a href="{{ route('funeral.packages.edit', $package->id) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('funeral.packages.destroy', $package->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this package?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Modal -->
            <div class="modal fade" id="viewPackageModal{{ $package->id }}" tabindex="-1" aria-labelledby="viewPackageModalLabel{{ $package->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content bg-dark text-white border-secondary">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="viewPackageModalLabel{{ $package->id }}">Package Name: {{ $package->name }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Package Details:</strong> {{ $package->description ?: 'No description provided.' }}</p>
                            <hr class="border-secondary">
                            @foreach($package->categories as $category)
                                <div class="mb-3">
                                    <h6 class="text-warning">{{ $category->name }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-dark table-bordered border-secondary">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Description</th>
                                                    <th>Quantity</th>
                                                    <th>Price (each)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($category->items as $item)
                                                    <tr>
                                                        <td>{{ $item->name }}</td>
                                                        <td>{{ $item->description ?: 'N/A' }}</td>
                                                        <td>{{ $item->quantity }}</td>
                                                        <td>₱{{ number_format($item->price, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                            <hr class="border-secondary">
                            <p class="fs-5"><strong>Total Price:</strong> ₱{{ number_format($package->total_price, 2) }}</p>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End View Modal -->
        @endforeach
    </div>
    @endif
</div>

<script>
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
        event.preventDefault();
    }
}
</script>
</x-layouts.funeral>
