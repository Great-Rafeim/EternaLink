<x-layouts.funeral>

@php
    $details = $booking->detail;
    $statuses = [
        'pending'          => ['label' => 'Pending',            'color' => 'warning',   'icon' => 'hourglass-split'],
        'assigned'         => ['label' => 'Agent Assigned',     'color' => 'info',      'icon' => 'person-badge'],
        'confirmed'        => ['label' => 'Confirmed',          'color' => 'success',   'icon' => 'check-circle'],
        'in_progress'      => ['label' => 'Client Filling Forms','color' => 'secondary','icon' => 'pencil-square'],
        'for_initial_review' => ['label' => 'For Initial Review','color' => 'primary',  'icon' => 'journal-check'],
        'for_review'       => ['label' => 'For Final Review',   'color' => 'secondary', 'icon' => 'journal-check'],
        'ongoing'          => ['label' => 'Ongoing',            'color' => 'primary',   'icon' => 'arrow-repeat'],
        'done'             => ['label' => 'Completed',          'color' => 'success',   'icon' => 'award'],
        'declined'         => ['label' => 'Declined',           'color' => 'danger',    'icon' => 'x-circle'],
    ];
    $status = $statuses[$booking->status] ?? [
        'label' => ucfirst(str_replace('_', ' ', $booking->status)),
        'color' => 'secondary',
        'icon'  => 'question-circle'
    ];
    $useCustomized = $booking->customized_package_id && $booking->customizedPackage;
    $customized = $useCustomized ? $booking->customizedPackage : null;
    $packageItems = $customized
        ? $customized->items->map(function($ci) {
            return [
                'item'     => $ci->inventoryItem->name ?? '-',
                'category' => $ci->inventoryItem->category->name ?? '-',
                'brand'    => $ci->inventoryItem->brand ?? '-',
                'quantity' => $ci->quantity
            ];
        })
        : $booking->package->items->map(function($item) {
            return [
                'item'     => $item->name ?? '-',
                'category' => $item->category->name ?? '-',
                'brand'    => $item->brand ?? '-',
                'quantity' => $item->pivot->quantity ?? 1
            ];
        });
    $totalAmount = $customized
        ? $customized->custom_total_price
        : ($booking->package->total_price ?? (
            $booking->package->items->sum(fn($item) => ($item->pivot->quantity ?? 1) * ($item->selling_price ?? $item->price ?? 0))
        ));
    $showApproveDeny = in_array($booking->status, ['pending', 'for_review']);
@endphp

                        <div class="container py-5">
                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <a href="{{ route('funeral.bookings.index') }}" class="btn btn-link mb-3">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                    <div class="card shadow-lg border-0 rounded-4 p-4">
                                        <div class="card-body">
<div class="mb-3 row align-items-center" style="min-height:42px;">
    {{-- Left-aligned: Manage Service --}}
    <div class="col">
        @if($booking->status === \App\Models\Booking::STATUS_ONGOING || $booking->status === \App\Models\Booking::STATUS_COMPLETED)
            <a href="{{ route('funeral.bookings.manage-service', $booking->id) }}"
               class="btn btn-primary">
                <i class="bi bi-gear"></i> Manage Service
            </a>
        @endif
    </div>
    {{-- Right-aligned: Export PDF and Edit --}}
    <div class="col-auto d-flex gap-2 justify-content-end">
        <a href="{{ route('client.bookings.details.exportPdf', $booking->id) }}"
           class="btn btn-outline-primary"
           target="_blank">
            <i class="bi bi-filetype-pdf"></i> Export as PDF
        </a>
        @if(!in_array($booking->status, ['confirmed', 'for_initial_review', 'in_progress']))
            <a href="{{ route('funeral.bookings.editInfo', $booking->id) }}"
               class="btn btn-outline-warning">
                <i class="bi bi-pencil"></i>
                Edit Form(as Funeral Parlor)
            </a>
        @endif
    </div>
</div>

                                                {{-- STATUS --}}
                                                <div class="mb-4 d-flex align-items-center gap-3">
                                                    <span class="badge bg-{{ $status['color'] }}-subtle text-{{ $status['color'] }} px-3 py-2 fs-6">
                                                        <i class="bi bi-{{ $status['icon'] }}"></i> {{ $status['label'] }}
                                                    </span>
                                                    <span class="text-muted small">Booking #{{ $booking->id }}</span>
                                                </div>

                                                {{-- BUTTONS --}}

                                                {{-- 1st set: Accept/Reject (if status is "confirmed") --}}
                                                @if($booking->status === 'pending')
                                                    <form action="{{ route('funeral.bookings.accept', $booking->id) }}" method="POST" class="d-inline-block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-success rounded-pill px-4 mb-2">
                                                            <i class="bi bi-check2-circle"></i> Accept
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('funeral.bookings.reject', $booking->id) }}" method="POST" class="d-inline-block ms-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-danger rounded-pill px-4 mb-2">
                                                            <i class="bi bi-x-circle"></i> Reject
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- 2nd set: Approve/Deny (if status is "for_review") --}}
                                                @if($booking->status === 'for_review')
                                                    <button class="btn btn-success rounded-pill px-4 mb-2" data-bs-toggle="modal" data-bs-target="#approveModal">
                                                        <i class="bi bi-check2-circle"></i> Approve
                                                    </button>
                                                    <form action="{{ route('funeral.bookings.deny', $booking->id) }}" method="POST" class="d-inline-block ms-1">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-danger rounded-pill px-4 mb-2">
                                                            <i class="bi bi-x-circle"></i> Deny
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- 3rd set: Service Start (if status is "approved") --}}
                                                @if($booking->status === \App\Models\Booking::STATUS_APPROVED)
                                                    <form action="{{ route('funeral.bookings.startService', $booking->id) }}" method="POST" class="d-inline-block">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-primary rounded-pill px-4 mb-2">
                                                            <i class="bi bi-play-circle"></i> Service Start
                                                        </button>
                                                    </form>
                                                @endif



                            <!-- Approve Modal (Bootstrap 5) -->
                            <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <form method="POST" action="{{ route('funeral.bookings.approve', $booking->id) }}" class="modal-content border-0 shadow">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header bg-primary text-white border-0 rounded-top">
                                    <h5 class="modal-title" id="approveModalLabel">
                                    <i class="bi bi-boxes me-2"></i>
                                    Inventory Impact
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body pb-0">
                                    <p class="text-muted mb-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <span>The following consumable items will have their stocks updated upon approval:</span>
                                    </p>
                                    <div class="table-responsive rounded-3 shadow-sm mb-2">
                                    <table class="table table-bordered table-hover align-middle mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th class="text-center">To Deduct</th>
                                            <th class="text-center">Available Quantity</th>
                                            <th class="text-center">Quantity Left<br><span class="fw-normal small">(after approval)</span></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $components = [];
                                            if ($booking->customized_package_id && $booking->customizedPackage) {
                                                foreach ($booking->customizedPackage->items as $item) {
                                                    $invItem = $item->inventoryItem;
                                                    if (!$invItem) continue;
                                                    $category = $invItem->category;
                                                    if ($category && !$category->is_asset) {
                                                        $qty = $item->quantity;
                                                        $available = $invItem->quantity;
                                                        $left = $available - $qty;
                                                        $components[] = [
                                                            'name' => $invItem->name,
                                                            'category' => $category->name ?? '-',
                                                            'brand' => $invItem->brand ?? '-',
                                                            'qty' => $qty,
                                                            'available' => $available,
                                                            'left' => $left,
                                                            'low_stock_threshold' => $invItem->low_stock_threshold,
                                                        ];
                                                    }
                                                }
                                            } else {
                                                $servicePackage = $booking->package;
                                                if ($servicePackage && $servicePackage->components) {
                                                    foreach ($servicePackage->components as $component) {
                                                        $invItem = $component->inventoryItem;
                                                        $category = $component->inventoryCategory ?? $invItem?->category;
                                                        if ($invItem && $category && !$category->is_asset) {
                                                            $qty = $component->quantity;
                                                            $available = $invItem->quantity;
                                                            $left = $available - $qty;
                                                            $components[] = [
                                                                'name' => $invItem->name,
                                                                'category' => $category->name ?? '-',
                                                                'brand' => $invItem->brand ?? '-',
                                                                'qty' => $qty,
                                                                'available' => $available,
                                                                'left' => $left,
                                                                'low_stock_threshold' => $invItem->low_stock_threshold,
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp

                                        @forelse($components as $row)
                                            <tr>
                                            <td class="fw-semibold">{{ $row['name'] }}</td>
                                            <td>{{ $row['category'] }}</td>
                                            <td>{{ $row['brand'] }}</td>
                                            <td class="text-danger text-center">-{{ $row['qty'] }}</td>
                                            <td class="text-center">{{ $row['available'] }}</td>
                                            <td class="text-center {{ ($row['low_stock_threshold'] && $row['left'] <= $row['low_stock_threshold']) ? 'text-warning fw-bold' : '' }}">
                                                {{ $row['left'] }}
                                                @if($row['low_stock_threshold'] && $row['left'] <= $row['low_stock_threshold'])
                                                <span class="badge bg-warning text-dark ms-1">Low</span>
                                                @endif
                                            </td>
                                            </tr>
                                        @empty
                                            <tr>
                                            <td colspan="6" class="text-center text-muted">No consumable items to deduct for this booking.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>

                            @php
                                // Use your logic to extract assets and asset categories
                                // Filter assets (is_asset == true)
                                $assets = collect($packageItems)->filter(fn($pkg) => $pkg['is_asset'] ?? false);
                                // Collect asset category IDs already listed
                                $assetCategoryIdsInItems = $assets->pluck('category_id')->unique()->toArray();

                                // Gather all asset categories from $assetCategories (should be passed from controller or view composer if not present)
                                $allAssetCategories = $assetCategories ?? [];

                                // Booking schedule
                                $details = $booking->detail;
                                $scheduleStart = $details?->wake_start_date ? \Carbon\Carbon::parse($details->wake_start_date)->format('M d, Y') : null;
                                $scheduleEnd = $details?->interment_cremation_date
                                    ? \Carbon\Carbon::parse($details->interment_cremation_date)->format('M d, Y')
                                    : ($details?->wake_end_date ? \Carbon\Carbon::parse($details->wake_end_date)->format('M d, Y') : null);
                                $scheduleRange = $scheduleStart && $scheduleEnd
                                    ? $scheduleStart . ' – ' . $scheduleEnd
                                    : ($scheduleStart ?: '—');
                            @endphp

                            @if($assets->count() || (isset($allAssetCategories) && count($allAssetCategories)))
                                <div class="mt-4">
                                    <h6 class="text-secondary mb-2">
                                        <i class="bi bi-truck-front me-1"></i>
                                        Bookable Assets Included in Package
                                    </h6>
                                    <div class="table-responsive rounded-3 shadow-sm">
                                        <table class="table table-bordered table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Asset/Item</th>
                                                    <th>Category</th>
                                                    <th class="text-center">Scheduled Use</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {{-- Assigned/selected assets --}}
                                                @forelse($assets as $pkg)
                                                    <tr>
                                                        <td>
                                                            {{ $pkg['item'] }}
                                                            <span class="badge bg-secondary ms-1">Asset</span>
                                                        </td>
                                                        <td>{{ $pkg['category'] }}</td>
                                                        <td class="text-center">{!! $scheduleRange !!}</td>
                                                    </tr>
                                                @empty
                                                @endforelse

                                                {{-- Show asset categories with no assigned item --}}
                                                @if(isset($allAssetCategories))
                                                    @foreach($allAssetCategories as $assetCategory)
                                                        @if(!in_array($assetCategory->id, $assetCategoryIdsInItems))
                                                            <tr>
                                                                <td class="fst-italic text-muted">To be decided</td>
                                                                <td><span class="fw-semibold">{{ $assetCategory->name }}</span></td>
                                                                <td class="text-center">{!! $scheduleRange !!}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="alert alert-secondary mt-2 small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Bookable assets will be assigned and reserved when the service starts. The scheduled use is based on the current wake/burial/interment dates.
                                    </div>
                                </div>
                            @endif




                                    
                                    </div>







                                    <div class="alert alert-info mt-3 small px-3 py-2">
                                    <b>Notes:</b>
                                    <ul class="mb-0 ps-3 small">
                                        <li>Only consumable item stocks will be deducted upon approval.</li>
                                        <li>If stock is insufficient, the approval will fail.</li>
                                        <li>Assets (e.g., vehicles, equipment) will be assigned and reserved when the service starts, not at this stage.</li>
                                    </ul>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light border-0 rounded-bottom">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    Cancel
                                    </button>
                                    <button type="submit" class="btn btn-success px-4">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    Confirm Approve &amp; Deduct
                                    </button>
                                </div>
                                </form>
                            </div>
                            </div>



{{-- PACKAGE HEADER: Name + Final Price --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <h3 class="fw-bold text-primary mb-0">
        {{ $booking->package->name ?? 'N/A' }}
    </h3>
    <div class="text-end">
        <div class="fw-bold" style="font-size:1.3rem">
            Final Amount:
            <span class="text-success">
                ₱{{ number_format($booking->final_amount ?? $totalAmount, 2) }}
            </span>
        </div>
        @if(($booking->final_amount ?? null) && ($booking->final_amount != $totalAmount))
            <div class="small text-muted">
                <i class="bi bi-info-circle"></i>
                Base Package Price: ₱{{ number_format($totalAmount, 2) }}
            </div>
        @endif
    </div>
</div>
<div class="mb-2 text-muted">{{ $booking->funeralHome->name ?? 'N/A' }}</div>
<hr class="my-4">



                        {{-- PACKAGE ITEM LIST --}}
<h5 class="mb-3"><i class="bi bi-box"></i> Package Inclusions</h5>
<ul class="list-group mb-2">
    <li class="list-group-item bg-light py-2">
        <strong class="text-primary"><i class="bi bi-droplet-half me-1"></i>Items</strong>
    </li>
    <li class="list-group-item d-flex fw-bold bg-light border-bottom-0">
        <span class="flex-fill">Item</span>
        <span class="flex-fill">Category</span>
        <span class="flex-fill">Brand</span>
        <span style="width:80px;">Qty</span>
    </li>
    @php
        // Filter consumables (is_asset == false)
        $consumables = collect($packageItems)->filter(fn($pkg) => !($pkg['is_asset'] ?? false));
        // Filter assets (is_asset == true)
        $assets = collect($packageItems)->filter(fn($pkg) => $pkg['is_asset'] ?? false);
        // Collect asset category IDs already listed
        $assetCategoryIdsInItems = $assets->pluck('category_id')->unique()->toArray();
    @endphp
    @forelse($consumables as $pkg)
        <li class="list-group-item d-flex">
            <span class="flex-fill">{{ $pkg['item'] }}</span>
            <span class="flex-fill">{{ $pkg['category'] }}</span>
            <span class="flex-fill">{{ $pkg['brand'] }}</span>
            <span style="width:80px;">{{ $pkg['quantity'] }}</span>
        </li>
    @empty
        <li class="list-group-item text-muted fst-italic">No consumable items included.</li>
    @endforelse

    <li class="list-group-item bg-light py-2 mt-2">
        <strong class="text-secondary"><i class="bi bi-truck-front me-1"></i>Bookable Assets/Items</strong>
    </li>
    <li class="list-group-item d-flex fw-bold bg-light border-bottom-0">
        <span class="flex-fill">Asset/Item</span>
        <span class="flex-fill">Category</span>

    </li>
    @forelse($assets as $pkg)
        <li class="list-group-item d-flex bg-secondary bg-opacity-25">
            <span class="flex-fill">
                {{ $pkg['item'] }}
                <span class="badge bg-secondary ms-1">Asset</span>
            </span>
            <span class="flex-fill">{{ $pkg['category'] }}</span>
            <span class="flex-fill">{{ $pkg['brand'] }}</span>
            <span style="width:80px;">{{ $pkg['quantity'] }}</span>
        </li>
    @empty
        {{-- If no asset items, this block will be skipped and asset categories will still be listed below --}}
    @endforelse

    {{-- Show asset categories with no assigned item --}}
    @foreach($assetCategories ?? [] as $assetCategory)
        @if(!in_array($assetCategory->id, $assetCategoryIdsInItems))
            <li class="list-group-item d-flex bg-secondary bg-opacity-25">
                <span class="flex-fill text-muted fst-italic">To be decided</span>
                <span class="flex-fill">
                    <span class="fw-semibold">{{ $assetCategory->name }}</span>
                    
                </span>
                

            </li>
        @endif
    @endforeach
</ul>

                        {{-- SECTION: Deceased Personal Details --}}
                        <h5 class="mb-2"><i class="bi bi-person-vcard"></i> Deceased Personal Details</h5>
                        <dl class="row mb-4">
                            <dt class="col-sm-5 text-secondary">Full Name</dt>
                            <dd class="col-sm-7">
                                {{ collect([
                                    $details?->deceased_first_name,
                                    $details?->deceased_middle_name,
                                    $details?->deceased_last_name
                                ])->filter()->join(' ') ?: '—' }}
                                @if($details?->deceased_nickname)
                                    <span class="text-muted">(“{{ $details->deceased_nickname }}”)</span>
                                @endif
                            </dd>
                            <dt class="col-sm-5 text-secondary">Residence</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_residence ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Sex</dt>
                            <dd class="col-sm-7">
                                @if(isset($details->deceased_sex))
                                    {{ $details->deceased_sex === 'M' ? 'Male' : ($details->deceased_sex === 'F' ? 'Female' : $details->deceased_sex) }}
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-sm-5 text-secondary">Civil Status</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_civil_status ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Birthday</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_birthday ? \Carbon\Carbon::parse($details->deceased_birthday)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Age</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_age ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Date of Death</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_date_of_death ? \Carbon\Carbon::parse($details->deceased_date_of_death)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Time of Death</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_time_of_death ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Cause of Death</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_cause_of_death ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Place of Death</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_place_of_death ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Religion</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_religion ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Occupation</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_occupation ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Citizenship</dt>
                            <dd class="col-sm-7">{{ $details?->deceased_citizenship ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Father's Name</dt>
                            <dd class="col-sm-7">
                                {{ collect([
                                    $details?->deceased_father_first_name,
                                    $details?->deceased_father_middle_name,
                                    $details?->deceased_father_last_name
                                ])->filter()->join(' ') ?: '—' }}
                            </dd>
                            <dt class="col-sm-5 text-secondary">Mother's Maiden Name</dt>
                            <dd class="col-sm-7">
                                {{ collect([
                                    $details?->deceased_mother_first_name,
                                    $details?->deceased_mother_middle_name,
                                    $details?->deceased_mother_last_name
                                ])->filter()->join(' ') ?: '—' }}
                            </dd>
                            <dt class="col-sm-5 text-secondary">Corpse Disposal</dt>
                            <dd class="col-sm-7">{{ $details?->corpse_disposal ?? '—' }}</dd>
                        </dl>

                        {{-- SECTION: Documents --}}
                        <h5 class="mb-2"><i class="bi bi-file-earmark-text"></i> Documents</h5>
                        <dl class="row mb-4">
                            <dt class="col-sm-5 text-secondary">Death Certificate Registration No.</dt>
                            <dd class="col-sm-7">{{ $details?->death_cert_registration_no ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Death Certificate Released To</dt>
                            <dd class="col-sm-7">{{ $details?->death_cert_released_to ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Death Certificate Release Date</dt>
                            <dd class="col-sm-7">{{ $details?->death_cert_released_date ? \Carbon\Carbon::parse($details->death_cert_released_date)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Death Certificate Signature</dt>
                            <dd class="col-sm-7">
                                @if(!empty($details?->death_cert_released_signature))
                                    <img src="{{ $details->death_cert_released_signature }}" alt="Signature" style="max-width:160px">
                                @else
                                    —
                                @endif
                            </dd>

                                {{-- VIEW DEATH CERTIFICATE FILE (PDF/JPG/PNG) --}}
    <dt class="col-sm-5 text-secondary align-self-center">Death Certificate File</dt>
    <dd class="col-sm-7">
        @if($details?->death_certificate_path)
            @php
                $fileUrl = asset('storage/' . $details->death_certificate_path);
                $ext = strtolower(pathinfo($details->death_certificate_path, PATHINFO_EXTENSION));
                $isPdf = $ext === 'pdf';
                $isImg = in_array($ext, ['jpg','jpeg','png']);
            @endphp
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i> View
                </a>
                <a href="{{ $fileUrl }}" download class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> Download
                </a>

            </div>
            @if($isImg)
                <div class="mt-2 border rounded shadow-sm p-2" style="max-width:350px;">
                    <img src="{{ $fileUrl }}" alt="Death Certificate Image" class="img-fluid">
                </div>
            @endif
        @else
            <span class="text-warning">No file uploaded</span>
        @endif
    </dd>

                            <dt class="col-sm-5 text-secondary">Funeral Contract No.</dt>
                            <dd class="col-sm-7">{{ $details?->funeral_contract_no ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Funeral Contract Released To</dt>
                            <dd class="col-sm-7">{{ $details?->funeral_contract_released_to ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Funeral Contract Release Date</dt>
                            <dd class="col-sm-7">{{ $details?->funeral_contract_released_date ? \Carbon\Carbon::parse($details->funeral_contract_released_date)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Funeral Contract Signature</dt>
                            <dd class="col-sm-7">
                                @if(!empty($details?->funeral_contract_released_signature))
                                    <img src="{{ $details->funeral_contract_released_signature }}" alt="Signature" style="max-width:160px">
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-sm-5 text-secondary">Official Receipt No.</dt>
                            <dd class="col-sm-7">{{ $details?->official_receipt_no ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Official Receipt Released To</dt>
                            <dd class="col-sm-7">{{ $details?->official_receipt_released_to ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Official Receipt Release Date</dt>
                            <dd class="col-sm-7">{{ $details?->official_receipt_released_date ? \Carbon\Carbon::parse($details->official_receipt_released_date)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Official Receipt Signature</dt>
                            <dd class="col-sm-7">
                                @if(!empty($details?->official_receipt_released_signature))
                                    <img src="{{ $details->official_receipt_released_signature }}" alt="Signature" style="max-width:160px">
                                @else
                                    —
                                @endif
                            </dd>
                        </dl>
{{-- SECTION: Informant --}}
<h5 class="mb-2"><i class="bi bi-person"></i> Informant Details</h5>
<dl class="row mb-4">
    <dt class="col-sm-5 text-secondary">Name</dt>
    <dd class="col-sm-7">{{ $details?->informant_name ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Age</dt>
    <dd class="col-sm-7">{{ $details?->informant_age ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Civil Status</dt>
    <dd class="col-sm-7">{{ $details?->informant_civil_status ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Relationship to Deceased</dt>
    <dd class="col-sm-7">{{ $details?->informant_relationship ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Contact No.</dt>
    <dd class="col-sm-7">{{ $details?->informant_contact_no ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Address</dt>
    <dd class="col-sm-7">{{ $details?->informant_address ?? '—' }}</dd>
</dl>

{{-- SECTION: Service & Payment --}}
<h5 class="mb-2"><i class="bi bi-cash-stack"></i> Service and Payment</h5>
<dl class="row mb-4">
    <dt class="col-sm-5 text-secondary">Service</dt>
    <dd class="col-sm-7">{{ $details?->service ?? $booking->package->name ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Package Amount</dt>
    <dd class="col-sm-7">₱{{ number_format($details?->amount ?? $totalAmount, 2) }}</dd>
    <dt class="col-sm-5 text-secondary">Other Fee</dt>
    <dd class="col-sm-7">
        @if($details?->other_fee)
            ₱{{ number_format($details->other_fee, 2) }}
        @else
            <span class="text-warning">Not set</span>
        @endif
    </dd>
    <dt class="col-sm-5 text-secondary">Deposit</dt>
    <dd class="col-sm-7">{{ $details?->deposit ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">CSWD</dt>
    <dd class="col-sm-7">{{ $details?->cswd ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">DSWD</dt>
    <dd class="col-sm-7">{{ $details?->dswd ?? '—' }}</dd>
    <dt class="col-sm-5 text-secondary">Remarks</dt>
    <dd class="col-sm-7">{{ $details?->remarks ?? '—' }}</dd>
</dl>

@if($booking->status === \App\Models\Booking::STATUS_FOR_INITIAL_REVIEW && auth()->id() === $booking->funeral_home_id)
    <form action="{{ route('funeral.bookings.updateOtherFees', $booking->id) }}" method="POST" class="row g-3 mb-3">
        @csrf
        @method('PATCH')
        <div class="col-md-4">
            <label class="form-label">Other Fee <span class="text-danger">*</span></label>
            <input type="number" name="other_fee" min="0" step="0.01" class="form-control"
                   value="{{ old('other_fee', $booking->detail->other_fee ?? '') }}" required>
        </div>
        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Save Other Fee
            </button>
        </div>
    </form>
@endif
    {{-- Update Payment Remarks --}}
    <form action="{{ route('funeral.bookings.updatePaymentRemarks', $booking->id) }}" method="POST" class="row g-3 mb-3">
        @csrf
        @method('PATCH')
        <div class="col-md-8">
            <label class="form-label">Payment Remarks</label>
            <textarea name="remarks" class="form-control" rows="2"
                      placeholder="(Optional)">{{ old('remarks', $booking->detail->remarks ?? '') }}</textarea>
        </div>
        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-chat-left-text"></i> Save Remarks
            </button>
        </div>
    </form>

                        {{-- SECTION: Certification --}}
                        <h5 class="mb-2"><i class="bi bi-patch-check"></i> Certification</h5>
                        <dl class="row mb-4">
                            <dt class="col-sm-5 text-secondary">Certifier Name</dt>
                            <dd class="col-sm-7">{{ $details?->certifier_name ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Relationship to Deceased</dt>
                            <dd class="col-sm-7">{{ $details?->certifier_relationship ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Residence</dt>
                            <dd class="col-sm-7">{{ $details?->certifier_residence ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Amount in Words</dt>
                            <dd class="col-sm-7">{{ $details?->certifier_amount ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Signature</dt>
                            <dd class="col-sm-7">
                                @if(!empty($details?->certifier_signature_image))
                                    <img src="{{ $details->certifier_signature_image }}" alt="Signature" style="max-width:160px">
                                @else
                                    {{ $details?->certifier_signature ?? '—' }}
                                @endif
                            </dd>
                        </dl>

                        {{-- PHASE 2: Wake, Burial & Customization --}}
                        <h5 class="mb-2"><i class="bi bi-calendar2-week"></i> Wake, Burial & Customization</h5>
                        @if($details)
                        <dl class="row mb-4">
                            <dt class="col-sm-5 text-secondary">Wake Start Date</dt>
                            <dd class="col-sm-7">{{ $details?->wake_start_date ? \Carbon\Carbon::parse($details->wake_start_date)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Wake End Date</dt>
                            <dd class="col-sm-7">{{ $details?->wake_end_date ? \Carbon\Carbon::parse($details->wake_end_date)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Burial/Interment Date</dt>
                            <dd class="col-sm-7">{{ $details?->interment_cremation_date ? \Carbon\Carbon::parse($details->interment_cremation_date)->format('M d, Y') : '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Burial/Interment Time</dt>
                            <dd class="col-sm-7">{{ $details?->interment_cremation_time ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Cemetery / Crematory</dt>
                            <dd class="col-sm-7">{{ $details?->cemetery_or_crematory ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Plot Reserved?</dt>
                            <dd class="col-sm-7">
                                @if(!is_null($details?->has_plot_reserved))
                                    <span class="badge bg-{{ $details->has_plot_reserved ? 'success' : 'secondary' }}">
                                        {{ $details->has_plot_reserved ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-sm-5 text-secondary">Preferred Attire</dt>
                            <dd class="col-sm-7">{{ $details?->attire ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Post Services</dt>
                            <dd class="col-sm-7">{{ $details?->post_services ?? '—' }}</dd>
                        </dl>
                        @endif

<h5 class="mb-2"><i class="bi bi-person-badge"></i> Assigned Agent</h5>
@php
    $bookingAgent = $booking->bookingAgent;
    $agentUser = $bookingAgent?->agentUser; // This will be the User model if assigned
@endphp

@if($bookingAgent)
    @if($bookingAgent->need_agent === 'yes')
        @if($bookingAgent->agent_type === 'client')
            <div class="mb-3">
                <div class="mb-2">
                    <span class="badge bg-info text-dark me-2">Client Agent</span>
                    @if($agentUser)
                        {{-- Agent assigned, show name and email --}}
                        <strong>{{ $agentUser->name }}</strong>
                        <span class="text-muted small ms-2">{{ $agentUser->email }}</span>
                    @elseif($bookingAgent->client_agent_email)
                        {{-- Email provided but agent not yet assigned --}}
                        <strong>{{ $bookingAgent->client_agent_email }}</strong>
                    @else
                        <span class="text-muted">No agent email provided.</span>
                    @endif
                </div>
                {{-- Show button or status based on invitation --}}
                @if(!$agentUser && $bookingAgent->client_agent_email)
                    @if($invitationStatus === 'pending')
                        <span class="text-success">Invitation already sent. Awaiting agent response.</span>
                    @elseif($invitationStatus === 'accepted')
                        <span class="badge bg-success">Agent Invitation Accepted</span>
                    @else
                        <form action="{{ route('funeral.bookings.agent-invite', $booking->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-envelope"></i> Send Agent Invitation
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        @elseif($bookingAgent->agent_type === 'parlor')
            @if($agentUser)
                <div class="d-flex align-items-center gap-2">
                    
                    <div>
                        <div>{{ $agentUser->name }}</div>
                        <div class="text-muted small">{{ $agentUser->email }} <span class="badge bg-secondary">Funeral Parlor Agent</span></div>
                    </div>
                </div>
                
            @else
                {{-- Assign agent form --}}
                <form action="{{ route('funeral.bookings.assign-agent', $booking->id) }}" method="POST" class="mb-3 d-flex align-items-center gap-2">
                    @csrf
                    <label for="agent_user_id" class="me-2 mb-0"><i class="bi bi-person"></i> Assign Agent:</label>
                    <select name="agent_user_id" id="agent_user_id" class="form-select w-auto" required>
                        <option value="">-- Select Agent --</option>
                        @foreach($parlorAgents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->email }})</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Assign</button>
                </form>
                @if($parlorAgents->isEmpty())
                    <span class="text-muted">No available agents to assign.</span>
                @endif
            @endif
        @endif
    @else
        <span class="text-muted mb-4 d-block">No agent needed for this booking.</span>
    @endif
@else
    <span class="text-muted mb-4 d-block">No agent assignment record found.</span>
@endif

                    {{-- SECTION: Cemetery & Plot Details --}}
                    <h5 class="mb-2 mt-4"><i class="bi bi-building"></i> Cemetery Details</h5>
                    <dl class="row mb-4">
                        @php
                            $cemetery = $booking->cemeteryBooking->cemetery ?? null;
                            $plot = $booking->cemeteryBooking->plot ?? null;
                        @endphp

                        <dt class="col-sm-5 text-secondary">Cemetery Name</dt>
                        <dd class="col-sm-7">
                            {{ $cemetery?->user->name ?? 'Not specified' }}
                        </dd>

                        <dt class="col-sm-5 text-secondary">Cemetery Address</dt>
                        <dd class="col-sm-7">
                            {{ $cemetery?->address ?? 'Not specified' }}
                        </dd>

                        <dt class="col-sm-5 text-secondary">Plot Number</dt>
                        <dd class="col-sm-7">
                            @if($plot)
                                {{ $plot->plot_number ?? 'Not specified' }}
                            @else
                                <span class="text-warning">Waiting for assignment</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-secondary">Section</dt>
                        <dd class="col-sm-7">
                            @if($plot)
                                {{ $plot->section ?? 'Not specified' }}
                            @else
                                <span class="text-warning">Waiting for assignment</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-secondary">Block</dt>
                        <dd class="col-sm-7">
                            @if($plot)
                                {{ $plot->block ?? 'Not specified' }}
                            @else
                                <span class="text-warning">Waiting for assignment</span>
                            @endif
                        </dd>
                    </dl>



                        {{-- TIMELINE --}}
                        <h5 class="mb-2"><i class="bi bi-clock-history"></i> Timeline</h5>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item">
                                <strong>Requested On:</strong>
                                <span class="ms-1">{{ $booking->created_at->format('M d, Y h:i A') }}</span>
                            </li>
                            <li class="list-group-item">
                                <strong>Last Updated:</strong>
                                <span class="ms-1">{{ $booking->updated_at->format('M d, Y h:i A') }}</span>
                            </li>
                            @if($booking->status === 'done')
                            <li class="list-group-item">
                                <strong>Completed On:</strong>
                                <span class="ms-1">{{ $booking->updated_at->format('M d, Y h:i A') }}</span>
                            </li>
                            @endif
                        </ul>

                        <a href="{{ route('funeral.bookings.index') }}" class="btn btn-outline-secondary rounded-pill mt-3 px-4 py-2">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.funeral>
