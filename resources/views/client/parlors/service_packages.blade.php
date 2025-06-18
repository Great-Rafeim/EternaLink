<x-client-layout>
    <div class="container px-0 px-md-3">
        {{-- Parlor Details Card --}}
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="card shadow border-0 rounded-4 mb-4">
                    <div class="row g-0">
                        <div class="col-md-4 d-flex align-items-center justify-content-center bg-light rounded-start-4">
                            @if($parlor->funeralParlor && $parlor->funeralParlor->image)
                                <img src="{{ asset('storage/'.$parlor->funeralParlor->image) }}" alt="Logo" class="img-fluid rounded-4" style="max-height: 180px; object-fit: contain;">
                            @else
                                <i class="bi bi-building" style="font-size: 3.5rem; color: #aab2bd;"></i>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h2 class="fw-bold mb-1" style="color: #1565c0;">{{ $parlor->name }}</h2>
                                @if($parlor->funeralParlor)
                                    <div class="mb-2 text-secondary">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $parlor->funeralParlor->address ?? 'No address provided' }}
                                    </div>
                                    <div class="mb-2">
                                        @if($parlor->funeralParlor->contact_email)
                                            <span class="me-3">
                                                <i class="bi bi-envelope me-1"></i>
                                                {{ $parlor->funeralParlor->contact_email }}
                                            </span>
                                        @endif
                                        @if($parlor->funeralParlor->contact_number)
                                            <span>
                                                <i class="bi bi-telephone me-1"></i>
                                                {{ $parlor->funeralParlor->contact_number }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mb-0">{{ $parlor->funeralParlor->description }}</p>
                                @else
                                    <div class="text-muted">No additional details available.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Service Packages List --}}
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h3 class="fw-bold mb-0" style="color: #1565c0;">Service Packages</h3>
            <a href="{{ route('client.parlors.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Parlors
            </a>
        </div>
        <div class="row g-4">
            @forelse($servicePackages as $package)
{{-- Card Layout (inside @foreach loop) --}}
<div class="col-12 col-md-6 col-lg-4 d-flex">
    <div class="card border-0 shadow-sm rounded-4 flex-fill h-100">
        @if($package->image)
            <img src="{{ asset('storage/'.$package->image) }}"
                class="card-img-top"
                alt="Package Image"
                style="height: 180px; object-fit: cover; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
        @else
            <div class="d-flex align-items-center justify-content-center bg-light"
                style="height: 180px; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
                <span class="text-muted text-center w-100">
                    <i class="bi bi-image" style="font-size: 2.5rem;"></i>
                    <div style="font-size: 1rem;">No Image</div>
                </span>
            </div>
        @endif

        <div class="card-body d-flex flex-column">
            <h5 class="card-title fw-semibold mb-2" style="color: #1565c0;">
                <i class="bi bi-box2-heart me-2"></i>
                {{ $package->name }}
            </h5>
            <p class="card-text text-secondary mb-3" style="min-height: 3em;">
                {{ Str::limit($package->description, 90) }}
            </p>
            <div class="mt-auto">
                <span class="badge bg-primary-subtle text-primary mb-2" style="font-size:1rem;">
                    ₱{{ number_format($package->total_price, 2) }}
                </span>
                <div class="d-flex justify-content-end">
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-2"
                        data-bs-toggle="modal"
                        data-bs-target="#viewPackageModal{{ $package->id }}"
                    >
                        <i class="bi bi-eye me-1"></i> View Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="viewPackageModal{{ $package->id }}" tabindex="-1" aria-labelledby="viewPackageModalLabel{{ $package->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-primary-subtle">
                <h5 class="modal-title" id="viewPackageModalLabel{{ $package->id }}">
                    <i class="bi bi-box-seam"></i> {{ $package->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @if($package->image)
                <img src="{{ asset('storage/'.$package->image) }}"
                    alt="Package Image"
                    class="img-fluid rounded-top"
                    style="max-height:180px;object-fit:cover;">
            @else
                <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-25"
                     style="height: 180px;">
                    <div class="text-center w-100">
                        <i class="bi bi-image" style="font-size: 2.5rem; color: #aab2bd;"></i>
                        <div class="text-muted small mt-1">No Image</div>
                    </div>
                </div>
            @endif
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Description:</strong> <br>
                    <span class="text-secondary">{{ $package->description ?: 'No description provided.' }}</span>
                </div>
                <hr>
                @php
                    $grouped = $package->items->groupBy(fn($item) => $item->category->name ?? 'Uncategorized');
                @endphp

                @foreach($grouped as $categoryName => $items)
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary mb-2">{{ $categoryName }}</h6>
                        <ul class="list-unstyled ms-3">
                            @foreach($items as $item)
                                <li>
                                    <i class="bi bi-dot"></i>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                    <span class="text-muted">x {{ $item->pivot->quantity ?? 1 }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <hr>
                <div class="fs-5 text-end">
                    <strong>Total Price:</strong>
                    <span class="text-success">₱{{ number_format($package->total_price, 2) }}</span>
                </div>
            </div>
        <div class="modal-footer border-0 d-flex justify-content-end">
            <button type="button" class="btn btn-secondary rounded-pill px-4 me-2" data-bs-dismiss="modal">
                <i class="bi bi-x-lg"></i> Close
            </button>
            <a href="{{ route('client.parlors.packages.book', $package->id) }}"
            class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-calendar-check"></i> Book
            </a>
        </div>
        </div>
    </div>
</div>

                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center rounded-4">
                                    No service packages available for this parlor at the moment.
                                </div>
                            </div>
            @endforelse
        </div>
    </div>
        </div>
    </div>
</div>  
</x-client-layout>
