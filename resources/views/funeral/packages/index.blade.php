<x-layouts.funeral>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white">Funeral Service Packages</h2>
        <a href="{{ route('funeral.packages.create') }}" class="btn btn-success shadow">+ Create Package</a>
    </div>

    @if($packages->isEmpty())
        <div class="text-center my-5">
            <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f5fa.png" alt="Empty" style="width:60px;">
            <p class="mt-3 text-muted">No packages created yet.<br>Click the button above to create your first package!</p>
        </div>
    @else
    <div class="row row-cols-1 row-cols-md-2 g-4">
        @foreach($packages as $package)
            <div class="col">
                <div class="card h-100 bg-dark text-white border-0 shadow-lg hover-shadow position-relative package-card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">{{ $package->name }}</h4>
                        <div class="mb-2 text-muted small">{{ $package->created_at->format('M d, Y') }}</div>
                        <p class="card-text">{{ $package->description ?: 'No description provided.' }}</p>
                    </div>
                    <div class="card-footer bg-dark border-0 pt-0 d-flex flex-column align-items-center">
                        <div class="mb-2 fs-5">
                            <span class="badge bg-gradient text-white p-2"><strong>Total:</strong> ₱{{ number_format($package->total_price, 2) }}</span>
                        </div>
                        <div class="d-flex gap-2 justify-content-center">
                            <button class="btn btn-outline-light btn-sm" title="View Package" data-bs-toggle="modal" data-bs-target="#viewPackageModal{{ $package->id }}">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <a href="{{ route('funeral.packages.edit', $package->id) }}" class="btn btn-warning btn-sm" title="Edit Package">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form action="{{ route('funeral.packages.destroy', $package->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" title="Delete Package">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
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
                            <h5 class="modal-title" id="viewPackageModalLabel{{ $package->id }}">
                                <i class="bi bi-box-seam"></i> {{ $package->name }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3"><strong>Description:</strong> {{ $package->description ?: 'No description provided.' }}</div>
                            <hr class="border-secondary">
                            @php
                                // We'll assume you have a relationship: $package->items()->with('categories')->get();
                                // If you want to group by category for the modal, prepare a grouped array:
                                $grouped = $package->items->groupBy(fn($item) => $item->category->name ?? 'Uncategorized');
                            @endphp

                            @foreach($grouped as $categoryName => $items)
                                <div class="mb-3">
                                    <h6 class="text-warning">{{ $categoryName }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-dark table-bordered border-secondary mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Quantity</th>
                                                    <th>Price (each)</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $item)
                                                    <tr>
                                                        <td>{{ $item->name }}</td>
                                                        <td>{{ $item->pivot->quantity ?? 1 }}</td>
                                                        <td>₱{{ number_format($item->selling_price, 2) }}</td>
                                                        <td>₱{{ number_format(($item->selling_price * ($item->pivot->quantity ?? 1)), 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                            <hr class="border-secondary">
                            <div class="fs-5 text-end">
                                <strong>Total Price:</strong> <span class="text-success">₱{{ number_format($package->total_price, 2) }}</span>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End View Modal -->
        @endforeach
    </div>
    @endif
</div>

<!-- Optional: Add Bootstrap Icons for a more modern look -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<script>
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
        event.preventDefault();
    }
}
</script>

<style>
.package-card:hover {
    box-shadow: 0 0 24px 0 rgba(70,255,220,0.3), 0 2px 10px 0 rgba(0,0,0,0.16);
    transition: box-shadow .2s;
}
</style>
</x-layouts.funeral>
