<x-layouts.funeral>
<style>
    .rounded-rectangle, .rounded-4 {
        border-radius: 1rem !important;
    }
</style>

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

<!-- Search, Filter, Sort controls -->
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

<br>
<br>

    <!-- Expose packages data to JS -->
    <script>
    window._allPackages = @json($packagesForJs);
    </script>

    <!-- Card grid for JS rendering -->
    <div id="package-cards-grid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4"></div>

    <!-- Modals: Render all to DOM for View buttons -->
    @foreach($packages as $package)
    <div class="modal fade" id="viewPackageModal{{ $package->id }}" tabindex="-1" aria-labelledby="viewPackageModalLabel{{ $package->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-dark text-white border-secondary rounded-4">
            <div class="modal-header border-secondary align-items-center">
                <h5 class="modal-title d-flex align-items-center gap-2 mb-0" id="viewPackageModalLabel{{ $package->id }}">
                    <i class="bi bi-box-seam"></i>
                    <span>{{ $package->name }}</span>
                </h5>
                @if($package->is_cremation)
                    <span class="badge badge-thin bg-warning text-dark ms-auto d-flex align-items-center"
                        title="Cremation Package">
                        <i class="bi bi-fire me-1"></i> Cremation
                    </span>
                @else
                    <span class="badge badge-thin bg-primary ms-auto d-flex align-items-center"
                        title="Burial Package">
                        <i class="bi bi-flower2 me-1"></i> Burial
                    </span>
                @endif
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

                <div class="modal-body pb-0">
                    {{-- Package Image --}}
                    @if($package->image)
                        <div class="mb-3 text-center">
                            <img src="{{ asset('storage/'.$package->image) }}"
                                 alt="Package Image"
                                 class="img-fluid rounded-3 shadow"
                                 style="max-height: 180px; object-fit: cover; cursor:pointer;"
                                 onclick="enlargeImage(this)">
                        </div>
                    @else
                        <div class="mb-3 d-flex align-items-center justify-content-center bg-secondary bg-opacity-25 rounded-3" style="height: 180px;">
                            <div class="text-center w-100">
                                <i class="bi bi-image" style="font-size: 2.5rem; color: #aab2bd;"></i>
                                <div class="text-muted small mt-1">No Image</div>
                            </div>
                        </div>
                    @endif

                    <div class="mb-4">
                        <div class="text-secondary small mb-1">
                            Created: {{ $package->created_at->format('F j, Y') }}
                        </div>
                        <div>
                            <strong>Description:</strong>
                            <span class="text-light">{{ $package->description ?: 'No description provided.' }}</span>
                        </div>
                    </div>

                    <hr class="border-secondary">

                    {{-- Consumable Items --}}
                    @php
                        $grouped = $package->items->groupBy(fn($item) => $item->category->name ?? 'Uncategorized');
                    @endphp

                    @foreach($grouped as $categoryName => $items)
                        <div class="mb-3">
                            <h6 class="text-warning fw-semibold mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-tag"></i>
                                {{ $categoryName }}
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-dark table-bordered border-secondary mb-0 align-middle">
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
                                                <td class="d-flex align-items-center gap-2">
                                                    @if($item->image)
                                                        <img src="{{ asset('storage/'.$item->image) }}"
                                                             alt="Item Image"
                                                             class="rounded shadow-sm"
                                                             style="height: 28px; width: 28px; object-fit: cover; cursor:pointer;"
                                                             onclick="enlargeImage(this)">
                                                    @endif
                                                    <span>{{ $item->name }}</span>
                                                </td>
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

                    {{-- Bookable Asset Categories --}}
                    @if($package->assetCategories->isNotEmpty())
                        <div class="mb-2">
                            <h6 class="text-info fw-semibold mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-box"></i>
                                Bookable Asset Categories
                            </h6>
                            <div class="row g-3">
                                @foreach($package->assetCategories as $assetCategory)
                                    <div class="col-md-6">
                                        <div class="card bg-secondary bg-opacity-10 border-0 rounded-4 shadow-sm h-100">
                                            <div class="card-body p-3 d-flex align-items-center gap-3">
                                                @if($assetCategory->inventoryCategory && $assetCategory->inventoryCategory->image)
                                                    <div class="rounded-4 border bg-white bg-opacity-25 d-flex align-items-center justify-content-center"
                                                         style="height: 150px; width: 150px; cursor:pointer;"
                                                         onclick="enlargeImage(this.querySelector('img'))">
                                                        <img src="{{ asset('storage/'.$assetCategory->inventoryCategory->image) }}"
                                                             alt="Category Image"
                                                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 1rem;">
                                                    </div>
                                                @else
                                                    <div class="rounded-4 border bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center"
                                                         style="height: 150px; width: 150px; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
                                                        <i class="bi bi-folder2-open" style="font-size: 2rem; color: #aab2bd;"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold text-light mb-1">{{ $assetCategory->inventoryCategory->name ?? 'Unknown' }}</div>
                                                    <div class="text-info small">
                                                        ₱{{ number_format($assetCategory->price, 2) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <hr class="border-secondary mt-4 mb-2">
                    <div class="fs-5 text-end">
                        @php
                            $total = $package->total_price ?? 0;
                            $vat = $total * 12 / 112;
                            $subtotal = $total - $vat;
                        @endphp
                        <strong>Total Price:</strong>
                        <span class="text-success">₱{{ number_format($total, 2) }}</span>
                    </div>
                    <div class="text-end mt-2" style="font-size: 1.08em;">
                        <span class="text-light-emphasis">
                            Subtotal: ₱{{ number_format($subtotal, 2) }}<br>
                            VAT (12%): <span class="text-warning">₱{{ number_format($vat, 2) }}</span>
                        </span>
                    </div>
                    <div class="text-end text-info small mt-1">
                        <i class="bi bi-info-circle"></i> Total price includes 12% VAT as required by Philippine law.
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
    @endforeach

    @endif
</div>

<!-- Reusable Image Lightbox Overlay -->
<div id="image-lightbox-overlay" 
     style="display:none; position:fixed; z-index:1060; left:0; top:0; width:100vw; height:100vh; background:rgba(10,10,10,0.87); align-items:center; justify-content:center;"
     onclick="hideEnlargedImage()">
    <img id="image-lightbox-img" src="" style="max-width:90vw; max-height:90vh; border-radius:14px; box-shadow:0 8px 32px #000;" />
    <button type="button"
            onclick="hideEnlargedImage(); event.stopPropagation();" 
            style="position:absolute;top:32px;right:40px;z-index:2;"
            class="btn btn-lg btn-light shadow">
        <i class="bi bi-x-lg"></i>
    </button>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<script>
// Card template renderer
function renderPackageCard(pkg) {
    return `
    <div class="col d-flex">
        <div class="card package-card flex-fill h-100 bg-dark text-white border-0 shadow-lg position-relative rounded-4 d-flex flex-column">
            ${pkg.image ? `
                <img src="${pkg.image}"
                     class="card-img-top"
                     alt="Package Image"
                     style="height: 180px; object-fit: cover; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
            ` : `
                <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-25"
                     style="height: 180px; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
                    <div class="text-center w-100">
                        <i class="bi bi-image" style="font-size: 2.5rem; color: #aab2bd;"></i>
                        <div class="text-muted small mt-1">No Image</div>
                    </div>
                </div>
            `}
<div class="card-body d-flex flex-column">
    <div class="d-flex align-items-center mb-1">
        <h4 class="card-title fw-bold mb-0" style="color: #90caf9; flex:1;">
            ${escapeHTML(pkg.name)}
        </h4>
        ${
            pkg.is_cremation
                ? `<span class="badge badge-thin bg-warning text-dark ms-auto d-flex align-items-center" 
                        title="Cremation Package" style="font-size:0.83em;">
                        <i class="bi bi-urn me-1"></i> Cremation
                   </span>`
                : `<span class="badge badge-thin bg-primary ms-auto d-flex align-items-center" 
                        title="Burial Package" style="font-size:0.83em;">
                        <i class="bi bi-flower2 me-1"></i> Burial
                   </span>`
        }
    </div>
    <div class="mb-2 text-muted small">${pkg.created_at_display}</div>
    <p class="card-text flex-grow-1" style="min-height:3.6em;">
        ${escapeHTML(pkg.description || 'No description provided.')}
    </p>
</div>

            <div class="card-footer bg-dark border-0 pt-0 d-flex flex-column align-items-center rounded-bottom-4">
                <div class="mb-2 fs-6">
                    <span class="badge bg-gradient text-white p-2" style="background:linear-gradient(90deg,#1565c0 60%,#29b6f6 100%)">
                        <strong>Total:</strong> ₱${Number(pkg.total_price).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}
                    </span>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-light btn-sm" title="View Package" data-bs-toggle="modal" data-bs-target="#viewPackageModal${pkg.id}">
                        <i class="bi bi-eye"></i> View
                    </button>
                    <a href="/funeral/packages/${pkg.id}/edit" class="btn btn-warning btn-sm" title="Edit Package">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="/funeral/packages/${pkg.id}" method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-danger btn-sm" title="Delete Package">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    `;
}

// Escape HTML
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

// Render all cards (filtered/sorted)
function renderPackagesList() {
    let pkgs = [...window._allPackages];
    const search = (document.getElementById('searchBar').value || '').toLowerCase();
    const filter = document.getElementById('filterType').value;
    const sortBy = document.getElementById('sortBy').value;

    // Filter by search
    if (search) {
        pkgs = pkgs.filter(pkg =>
            (pkg.name && pkg.name.toLowerCase().includes(search)) ||
            (pkg.description && pkg.description.toLowerCase().includes(search))
        );
    }

    // Filter by type
    if (filter === 'burial') pkgs = pkgs.filter(pkg => !pkg.is_cremation);
    if (filter === 'cremation') pkgs = pkgs.filter(pkg => pkg.is_cremation);

    // Sorting
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

    // Render or show empty
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

// Confirm delete dialog
function confirmDelete(event) {
    if (!confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
        event.preventDefault();
    }
}

// Image enlarge overlay (lightbox)
function enlargeImage(img) {
    const overlay = document.getElementById('image-lightbox-overlay');
    const overlayImg = document.getElementById('image-lightbox-img');
    overlayImg.src = img.src;
    overlay.style.display = 'flex';
    document.body.classList.add('overflow-hidden');
}
function hideEnlargedImage() {
    const overlay = document.getElementById('image-lightbox-overlay');
    overlay.style.display = 'none';
    document.getElementById('image-lightbox-img').src = '';
    document.body.classList.remove('overflow-hidden');
}
</script>

<style>
.badge-thin {
    font-size: 0.75em !important;
    padding: 0.18em 0.55em !important;
    font-weight: 500;
    border-radius: 0.4em;
    line-height: 1.1;
}
.package-card {
    min-height: 100%;
    display: flex;
    flex-direction: column;
    border-radius: 2rem;
}
.package-card:hover {
    box-shadow: 0 0 24px 0 rgba(70,255,220,0.3), 0 2px 10px 0 rgba(0,0,0,0.16);
    transition: box-shadow .2s;
}
.card-body {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.card-footer {
    border-bottom-left-radius: 2rem;
    border-bottom-right-radius: 2rem;
    background: #16181b;
}
.card-img-top, .bg-secondary.bg-opacity-25 {
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
}
</style>

</x-layouts.funeral>
