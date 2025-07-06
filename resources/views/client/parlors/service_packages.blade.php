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

        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="fw-bold mb-0" style="color: #1565c0;">Service Packages</h3>
            <a href="{{ route('client.parlors.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Parlors
            </a>
        </div>

        {{-- Search, Filter, Sort Controls --}}
        <div class="row mb-3 g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" id="searchBar" class="form-control" placeholder="Search package name or description..." style="font-size: 1rem;">
            </div>
            <div class="col-md-4">
                <select id="filterType" class="form-select" style="font-size: 1rem;">
                    <option value="">All Types</option>
                    <option value="burial">Burial Only</option>
                    <option value="cremation">Cremation Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="sortBy" class="form-select" style="font-size: 1rem;">
                    <option value="recent">Sort: Most Recent</option>
                    <option value="oldest">Sort: Oldest</option>
                    <option value="price_asc">Sort: Price (Low to High)</option>
                    <option value="price_desc">Sort: Price (High to Low)</option>
                    <option value="name_az">Sort: Name (A-Z)</option>
                    <option value="name_za">Sort: Name (Z-A)</option>
                </select>
            </div>
        </div>

        <script>
            window._allPackages = @json($packagesForJs);
        </script>

        <div id="package-cards-grid" class="row g-4"></div>

        {{-- Modals --}}
        @foreach($servicePackages as $package)
            <div class="modal fade" id="viewPackageModal{{ $package->id }}" tabindex="-1" aria-labelledby="viewPackageModalLabel{{ $package->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content border-0 rounded-4">
                        <div class="modal-header bg-primary-subtle rounded-top-4 align-items-center">
                            <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="viewPackageModalLabel{{ $package->id }}">
                                <i class="bi bi-box2-heart"></i>
                                <span>{{ $package->name }}</span>
                            </h5>
                            @if($package->is_cremation)
                                <span class="badge badge-thin bg-warning text-dark ms-auto d-flex align-items-center" title="Cremation Package" style="font-size:0.85em;">
                                    <i class="bi bi-fire me-1"></i> Cremation
                                </span>
                            @else
                                <span class="badge badge-thin bg-primary ms-auto d-flex align-items-center" title="Burial Package" style="font-size:0.85em;">
                                    <i class="bi bi-flower2 me-1"></i> Burial
                                </span>
                            @endif
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        {{-- Package Image --}}
                        @if($package->image)
                            <div class="d-flex align-items-center justify-content-center bg-dark bg-opacity-10 rounded-top-4" style="height: 200px;">
                                <img src="{{ asset('storage/'.$package->image) }}"
                                    alt="Package Image"
                                    class="img-fluid rounded-4 shadow-sm"
                                    style="max-height:180px; max-width:95%; cursor:pointer; object-fit:cover;"
                                    onclick="enlargeImage(this)">
                            </div>
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-25 rounded-top-4" style="height: 180px;">
                                <div class="text-center w-100">
                                    <i class="bi bi-image" style="font-size: 2.5rem; color: #aab2bd;"></i>
                                    <div class="text-muted small mt-1">No Image</div>
                                </div>
                            </div>
                        @endif

                        <div class="modal-body">
                            <div class="mb-3">
                                <strong>Description:</strong><br>
                                <span class="text-secondary">{{ $package->description ?: 'No description provided.' }}</span>
                            </div>
                            <hr>

                            {{-- Group Items --}}
                            @php
                                $grouped = $package->items->groupBy(fn($item) => $item->category->name ?? 'Uncategorized');
                                $consumableCats = [];
                                $assetCats = [];
                                foreach ($grouped as $catName => $items) {
                                    $catModel = $items->first()->category ?? null;
                                    if ($catModel && $catModel->is_asset) {
                                        $assetCats[$catName] = $items;
                                    } else {
                                        $consumableCats[$catName] = $items;
                                    }
                                }
                            @endphp

                            {{-- Consumables --}}
                            @foreach($consumableCats as $categoryName => $items)
                                @php $catModel = $items->first()->category ?? null; @endphp
                                <div class="mb-4">
                                    <div class="fw-bold text-primary mb-2">{{ $categoryName }}</div>
                                    <div class="row g-4">
                                        @foreach($items as $item)
                                            <div class="col-12 col-md-6 col-lg-4 text-center">
                                                <div class="bg-dark bg-opacity-10 rounded-4 p-2 h-100 d-flex flex-column align-items-center">
                                                    @if($item->image)
                                                        <div class="rounded-4 border bg-white bg-opacity-25 mb-2 d-flex align-items-center justify-content-center"
                                                            style="height: 90px; width: 100%; max-width: 90px; margin: 0 auto; cursor:pointer; overflow: hidden;"
                                                            onclick="enlargeImage(this.querySelector('img'))">
                                                            <img src="{{ asset('storage/'.$item->image) }}"
                                                                alt="Item Image"
                                                                style="width: 100%; height: 100%; object-fit: cover;">
                                                        </div>
                                                    @else
                                                        <div class="rounded-4 border bg-secondary bg-opacity-25 mb-2 d-flex align-items-center justify-content-center"
                                                            style="height: 90px; width: 100%; max-width: 90px; margin: 0 auto;">
                                                            <i class="bi bi-box" style="font-size:2rem; color:#aab2bd;"></i>
                                                        </div>
                                                    @endif
                                                    <div class="fw-semibold">{{ $item->name }}</div>
                                                    <div class="small text-muted">x{{ $item->pivot->quantity ?? 1 }}</div>
                                                    @if(isset($item->price))
                                                        <div class="small text-secondary">
                                                            Price: ₱{{ number_format($item->price, 2) }}
                                                            <br>
                                                            <span class="text-muted">Subtotal: ₱{{ number_format(($item->pivot->quantity ?? 1) * $item->price, 2) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{-- Asset Categories --}}
                            @if($package->assetCategories->isNotEmpty() || count($assetCats))
                                <hr>
                                <h6 class="fw-bold text-info mb-3 mt-3">Bookable Asset</h6>
                            @endif

                            @if($package->assetCategories->isNotEmpty())
                            <div class="row g-4 mb-3">
                                @foreach($package->assetCategories as $assetCategory)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="bg-secondary bg-opacity-10 border-0 rounded-4 shadow-sm p-3 text-center h-100">
                                            @if($assetCategory->inventoryCategory && $assetCategory->inventoryCategory->image)
                                                <div class="rounded-4 border bg-white bg-opacity-25 mb-2 mx-auto d-flex align-items-center justify-content-center"
                                                    style="height: 110px; width: 100%; max-width: 110px; cursor:pointer; overflow: hidden;"
                                                    onclick="enlargeImage(this.querySelector('img'))">
                                                    <img src="{{ asset('storage/'.$assetCategory->inventoryCategory->image) }}"
                                                        alt="Category Image"
                                                        style="width:100%;height:100%;object-fit:cover;">
                                                </div>
                                            @else
                                                <div class="rounded-4 border bg-secondary bg-opacity-25 mb-2 mx-auto d-flex align-items-center justify-content-center"
                                                    style="height: 110px; width: 100%; max-width: 110px;">
                                                    <i class="bi bi-folder2-open" style="font-size:2.5rem; color:#dde3ea;"></i>
                                                </div>
                                            @endif
                                            <div class="fw-bold text-dark fs-5 mb-1" style="letter-spacing: 0.5px;">
                                                {{ $assetCategory->inventoryCategory->name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-info fs-6 mb-1">₱{{ number_format($assetCategory->price, 2) }}</div>
                                            @if($assetCategory->inventoryCategory && $assetCategory->inventoryCategory->description)
                                                <div class="text-muted small">{{ $assetCategory->inventoryCategory->description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Asset Items (Grouped) --}}
                            @foreach($assetCats as $categoryName => $items)
                                @php $catModel = $items->first()->category ?? null; @endphp
                                <div class="mb-4">
                                    <div class="d-flex flex-column align-items-center mb-2">
                                        @if($catModel && $catModel->image)
                                            <div class="rounded-4 border mb-2" style="width: 90px; height: 90px; overflow:hidden; cursor:pointer;"
                                                 onclick="enlargeImage(this.querySelector('img'))">
                                                <img src="{{ asset('storage/'.$catModel->image) }}"
                                                     alt="Category Image"
                                                     style="width:100%;height:100%;object-fit:cover;">
                                            </div>
                                        @else
                                            <div class="rounded-4 border bg-secondary bg-opacity-25 mb-2 d-flex align-items-center justify-content-center"
                                                 style="width:90px; height:90px;">
                                                <i class="bi bi-folder2-open" style="font-size:2rem; color:#aab2bd;"></i>
                                            </div>
                                        @endif
                                        <div class="fw-bold text-primary mb-1">{{ $categoryName }}</div>
                                    </div>
                                    <div class="row g-4">
                                        @foreach($items as $item)
                                            <div class="col-12 col-md-6 text-center">
                                                <div class="bg-dark bg-opacity-10 rounded-4 p-2 h-100 d-flex flex-column align-items-center">
                                                    @if($item->image)
                                                        <div class="rounded-4 border bg-white bg-opacity-25 mb-2 d-flex align-items-center justify-content-center"
                                                             style="height: 90px; width: 100%; max-width: 90px; margin: 0 auto; cursor:pointer; overflow: hidden;"
                                                             onclick="enlargeImage(this.querySelector('img'))">
                                                            <img src="{{ asset('storage/'.$item->image) }}"
                                                                 alt="Item Image"
                                                                 style="width:100%;height:100%;object-fit:cover;">
                                                        </div>
                                                    @else
                                                        <div class="rounded-4 border bg-secondary bg-opacity-25 mb-2 d-flex align-items-center justify-content-center"
                                                             style="height: 90px; width: 100%; max-width: 90px; margin: 0 auto;">
                                                            <i class="bi bi-box" style="font-size:2rem; color:#aab2bd;"></i>
                                                        </div>
                                                    @endif
                                                    <div class="fw-semibold">{{ $item->name }}</div>
                                                    <div class="small text-muted">x{{ $item->pivot->quantity ?? 1 }}</div>
                                                    @if(isset($item->price))
                                                        <div class="small text-secondary">
                                                            Price: ₱{{ number_format($item->price, 2) }}
                                                            <br>
                                                            <span class="text-muted">Subtotal: ₱{{ number_format(($item->pivot->quantity ?? 1) * $item->price, 2) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{-- Final Price Breakdown --}}
                            <hr>
                            @php
                                $vatRate = 0.12;
                                $basePrice = $package->total_price / (1 + $vatRate);
                                $vatAmount = $package->total_price - $basePrice;
                            @endphp
                            <div class="fs-5 text-end mb-2">
                                <strong>Total Price:</strong>
                                <span class="text-success">₱{{ number_format($package->total_price, 2) }}</span>
                            </div>
                            <div class="text-end text-muted" style="font-size: 1em;">
                                <span>
                                    <b>Base Price (excl. VAT):</b> ₱{{ number_format($basePrice, 2) }}<br>
                                    <b>VAT (12%):</b> ₱{{ number_format($vatAmount, 2) }}
                                </span>
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
        @endforeach

        <!-- Reusable Enlarge Image Modal -->
        <div class="modal fade" id="enlargeImageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark border-0 rounded-4">
                    <div class="modal-body text-center p-0">
                        <img src="" id="enlargeImageTarget" class="img-fluid rounded-4" style="max-height: 70vh; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>

        {{-- Card rendering and filtering/sorting --}}
        <script>
        function escapeHTML(str) {
            return (str || '').replace(/[&<>'"]/g, function(tag) {
                const charsToReplace = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                };
                return charsToReplace[tag] || tag;
            });
        }
        function renderPackageCard(pkg) {
            return `
            <div class="col-12 col-md-6 col-lg-4 d-flex">
                <div class="card border-0 shadow-sm rounded-4 flex-fill h-100">
                    ${pkg.image ? `
                        <img src="${pkg.image}"
                             class="card-img-top"
                             alt="Package Image"
                             style="height: 180px; object-fit: cover; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
                    ` : `
                        <div class="d-flex align-items-center justify-content-center bg-light"
                            style="height: 180px; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
                            <span class="text-muted text-center w-100">
                                <i class="bi bi-image" style="font-size: 2.5rem;"></i>
                                <div style="font-size: 1rem;">No Image</div>
                            </span>
                        </div>
                    `}
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h5 class="card-title fw-semibold mb-0 flex-grow-1" style="color: #1565c0;">
                                <i class="bi bi-box2-heart me-2"></i>
                                ${escapeHTML(pkg.name)}
                            </h5>
                            ${
                                pkg.is_cremation
                                ? `<span class="badge badge-thin bg-warning text-dark ms-auto d-flex align-items-center" title="Cremation Package" style="font-size:0.85em;"><i class="bi bi-fire me-1"></i> Cremation</span>`
                                : `<span class="badge badge-thin bg-primary ms-auto d-flex align-items-center" title="Burial Package" style="font-size:0.85em;"><i class="bi bi-flower2 me-1"></i> Burial</span>`
                            }
                        </div>
                        <p class="card-text text-secondary mb-3" style="min-height: 3em;">
                            ${escapeHTML(pkg.description || 'No description provided.')}
                        </p>
                        <div class="mt-auto">
                            <span class="badge bg-primary-subtle text-primary mb-2" style="font-size:1rem;">
                                ₱${Number(pkg.total_price).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}
                            </span>
                            <div class="d-flex justify-content-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#viewPackageModal${pkg.id}"
                                >
                                    <i class="bi bi-eye me-1"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            `;
        }
        function renderPackagesList() {
            let pkgs = [...window._allPackages];
            const search = (document.getElementById('searchBar').value || '').toLowerCase();
            const filter = document.getElementById('filterType').value;
            const sortBy = document.getElementById('sortBy').value;

            if (search) {
                pkgs = pkgs.filter(pkg =>
                    (pkg.name && pkg.name.toLowerCase().includes(search)) ||
                    (pkg.description && pkg.description.toLowerCase().includes(search))
                );
            }
            if (filter === 'burial') pkgs = pkgs.filter(pkg => !pkg.is_cremation);
            if (filter === 'cremation') pkgs = pkgs.filter(pkg => pkg.is_cremation);

            pkgs.sort((a, b) => {
                switch (sortBy) {
                    case 'recent': return b.created_at.localeCompare(a.created_at);
                    case 'oldest': return a.created_at.localeCompare(b.created_at);
                    case 'price_asc': return a.total_price - b.total_price;
                    case 'price_desc': return b.total_price - a.total_price;
                    case 'name_az': return a.name.localeCompare(b.name);
                    case 'name_za': return b.name.localeCompare(a.name);
                    default: return 0;
                }
            });
            const grid = document.getElementById('package-cards-grid');
            grid.innerHTML = pkgs.length
                ? pkgs.map(renderPackageCard).join('')
                : `<div class="col-12 text-center py-5 text-muted">
                        <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f5fa.png" alt="Empty" style="width:60px;">
                        <div class="mt-3">No packages found.</div>
                   </div>`;
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('searchBar').addEventListener('input', renderPackagesList);
            document.getElementById('filterType').addEventListener('change', renderPackagesList);
            document.getElementById('sortBy').addEventListener('change', renderPackagesList);
            renderPackagesList();
        });
        function enlargeImage(img) {
            const modal = new bootstrap.Modal(document.getElementById('enlargeImageModal'));
            const target = document.getElementById('enlargeImageTarget');
            target.src = img.src;
            modal.show();
        }
        </script>

        <style>
            .badge { border-radius: 0.5em; }
            .badge.badge-thin { font-size: 0.88em; padding: 0.22em 0.65em; font-weight: 500; }
        </style>

        {{-- No packages fallback --}}
        @if($servicePackages->isEmpty())
            <div class="col-12">
                <div class="alert alert-info text-center rounded-4">
                    No service packages available for this parlor at the moment.
                </div>
            </div>
        @endif

    </div>
</x-client-layout>
