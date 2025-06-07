<x-layouts.funeral>
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-box-seam fs-2 text-primary me-2"></i>
            <h2 class="mb-0 fw-semibold">
                Request Item: <span class="text-primary">{{ $item->name }}</span>
            </h2>
        </div>

        <!-- Search Bar -->
        <form method="GET" class="mb-4">
            <div class="input-group shadow-sm rounded-pill overflow-hidden">
                <input type="text" name="search" class="form-control border-0 ps-4"
                       value="{{ old('search', $search) }}"
                       placeholder="🔍 Search for similar items...">
                <button class="btn btn-primary rounded-end-pill px-4 fw-semibold" type="submit">
                    Search
                </button>
            </div>
        </form>

        <!-- Item Grid -->
        <div class="row g-4">

            @php
                $filteredShareableItems = $shareableItems->filter(function($item) {
                    return $item->shareable_quantity > 0;
                });
            @endphp

            @forelse($filteredShareableItems as $share)

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow h-100 border-0 rounded-4 position-relative">
                        <!-- Badge for quantity -->
                        <span class="position-absolute top-0 end-0 badge bg-success fs-6 mt-2 me-2 px-3">
                            {{ $share->shareable_quantity }} Shareable stock
                        </span>
                        <div class="card-body py-4">
                            <!-- Icon + Name -->
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-archive fs-2 text-secondary me-2"></i>
                                <div>
                                    <h5 class="card-title fw-bold mb-1">{{ $share->name }}</h5>
                                    <div class="text-muted small">by {{ $share->funeralUser->name ?? 'Unknown' }}</div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-secondary">{{ $share->brand ?? 'Unbranded' }}</span>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small">Category: </span>
                                <span class="fw-semibold">{{ $share->category->name ?? '-' }}</span>
                            </div>
                            <form action="{{ route('funeral.partnerships.resource_requests.sendRequest', [$item->id, $share->id]) }}" method="POST">
                                    <a href="{{ route('funeral.partnerships.resource_requests.createRequestForm', [$item->id, $share->id]) }}"
                                    class="btn btn-outline-primary w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-send me-1"></i> Send Request
                                    </a>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center py-4 rounded-4 shadow-sm">
                        <i class="bi bi-emoji-frown fs-2"></i>
                        <div class="mt-2">No shareable items found from partner parlors.</div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-5 text-center">
            <a href="{{ route('funeral.items.index') }}" class="btn btn-secondary rounded-pill px-4 fw-semibold">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>
</x-layouts.funeral>
