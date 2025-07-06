<x-client-layout>

<style>
.timeline {
    position: relative;
    padding-left: 1.8rem;
}
.timeline-item {
    border-left: 2px solid #0d6efd22;
    margin-left: 0.3rem;
}
.timeline-dot {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 2px solid #fff;
    background: #0d6efd;
    left: -0.7rem;
    top: 1.2rem;
    z-index: 2;
}
.timeline-item:last-child {
    border-left: 2px solid transparent;
}
</style>


    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <a href="{{ route('client.dashboard') }}" class="btn btn-link mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>

@if($booking->status === \App\Models\Booking::STATUS_ONGOING || $booking->status === \App\Models\Booking::STATUS_COMPLETED)
    <div class="card shadow mb-4 border-0 rounded-4 animate__animated animate__fadeInDown">
        <div class="card-header bg-gradient bg-primary text-white py-3 rounded-top-4 d-flex align-items-center gap-2">
            <i class="bi bi-broadcast display-6"></i>
            <span class="fw-bold fs-5">Service Updates from Funeral Parlor</span>
        </div>
        <div class="card-body px-4 py-3 bg-light rounded-bottom-4">
            @if($serviceLogs->count())
                <div class="timeline">
                    @foreach($serviceLogs as $log)
                        <div class="timeline-item position-relative mb-4 pb-2 ps-4">
                            <span class="timeline-dot bg-primary position-absolute top-0 start-0 translate-middle-y"></span>
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-person-circle text-primary me-2"></i>
                                <span class="fw-semibold text-primary">{{ $log->user->name ?? 'Funeral Staff' }}</span>
                                <span class="text-muted small ms-2">
                                    <i class="bi bi-clock"></i>
                                    {{ $log->created_at->format('M d, Y h:i A') }}
                                </span>
                            </div>
                            <div class="ps-4">
                                <div class="alert alert-primary mb-0 py-2 px-3 shadow-sm border-0 rounded-3">
                                    {!! nl2br(e($log->message)) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle display-6 d-block mb-2"></i>
                    <span class="fw-medium">No updates have been posted by the funeral parlor yet.</span>
                </div>
            @endif
        </div>
    </div>
@endif

                <div class="card shadow-lg border-0 rounded-4 p-4">
                    <div class="card-body">

<div class="mb-3 d-flex justify-content-end align-items-center gap-2" style="min-height:42px;">
    <a href="{{ route('client.bookings.details.exportPdf', $booking->id) }}"
       class="btn btn-outline-primary"
       target="_blank">
        <i class="bi bi-filetype-pdf"></i> Export as PDF
    </a>

    @if($booking->certificate_released_at && $booking->certificate_signature)
        <a href="{{ route('client.bookings.download-certificate', $booking->id) }}"
           class="btn btn-outline-success"
           target="_blank">
            <i class="bi bi-award"></i>
            Download Certificate
        </a>
    @endif

    @if($booking->status === 'for_initial_review')
        <a href="{{ route('client.bookings.continue.edit', $booking->id) }}"
           class="btn btn-outline-secondary ms-1">
            <i class="bi bi-pencil-square"></i>
            Edit Booking Details
        </a>
    @elseif($booking->status === 'for_review')
        <a href="{{ route('client.bookings.continue.info', $booking->id) }}"
           class="btn btn-outline-warning ms-1">
            <i class="bi bi-pencil"></i>
            Edit Personal Details
        </a>
    @endif
</div>


                        
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
@endphp

                        {{-- STATUS --}}
                        <div class="mb-4 d-flex align-items-center gap-3">
                            <span class="badge bg-{{ $status['color'] }}-subtle text-{{ $status['color'] }} px-3 py-2 fs-6">
                                <i class="bi bi-{{ $status['icon'] }}"></i> {{ $status['label'] }}
                            </span>
                            <span class="text-muted small">Booking #{{ $booking->id }}</span>
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

<h5 class="mb-3"><i class="bi bi-box"></i> Package Inclusions</h5>

{{-- Consumable Items Table --}}
<div class="table-responsive mb-4">
    <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th colspan="6" class="bg-light text-primary">
                    <i class="bi bi-droplet-half me-1"></i>Items
                </th>
            </tr>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Brand</th>
                <th style="width:80px;">Qty</th>
                <th style="width:110px;">Unit Price</th>
                <th style="width:130px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @php
            $consumableTotal = 0;
            $isCustomized = $booking->customizedPackage && $booking->customizedPackage->items->count();
        @endphp

        @if($isCustomized)
            @foreach($booking->customizedPackage->items as $item)
                @php
                    $inventory = $item->inventoryItem;
                    $isAsset = $inventory->category->is_asset ?? false;
                @endphp
                @if(!$isAsset)
                    @php
                        $qty = $item->quantity ?? 1;
                        $unit = $item->unit_price ?? 0;
                        $subtotal = $qty * $unit;
                        $consumableTotal += $subtotal;
                    @endphp
                    <tr>
                        <td>{{ $inventory->name ?? '-' }}</td>
                        <td>{{ $inventory->category->name ?? '-' }}</td>
                        <td>{{ $inventory->brand ?? '-' }}</td>
                        <td>{{ $qty }}</td>
                        <td>₱{{ number_format($unit, 2) }}</td>
                        <td>₱{{ number_format($subtotal, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        @else
            @foreach($booking->package->items as $item)
                @php
                    $isAsset = $item->category->is_asset ?? false;
                @endphp
                @if(!$isAsset)
                    @php
                        $qty = $item->pivot->quantity ?? 1;
                        $unit = $item->selling_price ?? 0;
                        $subtotal = $qty * $unit;
                        $consumableTotal += $subtotal;
                    @endphp
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->category->name ?? '-' }}</td>
                        <td>{{ $item->brand ?? '-' }}</td>
                        <td>{{ $qty }}</td>
                        <td>₱{{ number_format($unit, 2) }}</td>
                        <td>₱{{ number_format($subtotal, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        @endif

        @if($consumableTotal > 0)
            <tr class="bg-light">
                <td colspan="5" class="text-end fw-semibold">Total</td>
                <td class="fw-semibold">₱{{ number_format($consumableTotal, 2) }}</td>
            </tr>
        @else
            <tr>
                <td colspan="6" class="text-muted fst-italic">No consumable items included.</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

{{-- Assets Table (shows asset item/category + category price) --}}
<div class="table-responsive mb-2">
    <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="bg-light text-secondary">
                    <i class="bi bi-truck-front me-1"></i>Bookable Assets/Items
                </th>
                <th style="width:140px;">Category Price</th>
            </tr>
        </thead>
        <tbody>
        @php
            $assetTotal = 0;
            $assetCategoryIdsInItems = [];
        @endphp

        @if($isCustomized)
            @foreach($booking->customizedPackage->items as $item)
                @php
                    $inventory = $item->inventoryItem;
                    $isAsset = $inventory->category->is_asset ?? false;
                    $catId = $inventory->category->id ?? null;
                    if($isAsset && $catId) $assetCategoryIdsInItems[] = $catId;
                    $catPrice = $isAsset && $catId ? ($assetCategoryPrices[$catId] ?? 0) : 0;
                    if($catPrice) $assetTotal += $catPrice;
                @endphp
                @if($isAsset)
                    <tr class="bg-secondary bg-opacity-25">
                        <td>
                            {{ $inventory->name ?? '-' }}
                            <span class="badge bg-secondary ms-1">Asset</span>
                        </td>
                        <td>₱{{ number_format($catPrice, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        @else
            @foreach($booking->package->items as $item)
                @php
                    $isAsset = $item->category->is_asset ?? false;
                    $catId = $item->category->id ?? null;
                    if($isAsset && $catId) $assetCategoryIdsInItems[] = $catId;
                    $catPrice = $isAsset && $catId ? ($assetCategoryPrices[$catId] ?? 0) : 0;
                    if($catPrice) $assetTotal += $catPrice;
                @endphp
                @if($isAsset)
                    <tr class="bg-secondary bg-opacity-25">
                        <td>
                            {{ $item->name }}
                            <span class="badge bg-secondary ms-1">Asset</span>
                        </td>
                        <td>₱{{ number_format($catPrice, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        @endif

        {{-- Show asset categories with no assigned item --}}
        @foreach($assetCategories ?? [] as $assetCategory)
            @if(!in_array($assetCategory->id, $assetCategoryIdsInItems))
                @php
                    $catPrice = $assetCategoryPrices[$assetCategory->id] ?? 0;
                    $assetTotal += $catPrice;
                @endphp
                <tr class="bg-secondary bg-opacity-25">
                    <td>
                        <span class="fw-semibold">{{ $assetCategory->name }}</span>
                        <span class="badge bg-secondary ms-1">Asset</span>
                    </td>
                    <td>₱{{ number_format($catPrice, 2) }}</td>
                </tr>
            @endif
        @endforeach

        @if(($assetCategories ?? collect())->count())
            <tr class="bg-light">
                <td class="text-end fw-semibold">Total</td>
                <td class="fw-semibold">₱{{ number_format($assetTotal, 2) }}</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>





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
    
    {{-- DECEASED IMAGE --}}
    @if(!empty($details?->deceased_image))
        <dt class="col-sm-5 text-secondary align-self-center">Deceased Image</dt>
        <dd class="col-sm-7">
            <a href="{{ asset('storage/' . $details->deceased_image) }}"
               target="_blank"
               class="deceased-img-preview-link"
               data-img="{{ asset('storage/' . $details->deceased_image) }}">
                <img src="{{ asset('storage/' . $details->deceased_image) }}"
                     alt="Deceased Image"
                     style="width:80px; height:80px; object-fit:cover; border-radius: 8px; border: 1px solid #ddd; cursor: pointer;">
            </a>
            <a href="{{ asset('storage/' . $details->deceased_image) }}"
               download
               class="btn btn-sm btn-outline-primary ms-2 align-middle">
                <i class="bi bi-download"></i> Download
            </a>
        </dd>
    @endif

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

{{-- Modal for image expand --}}
<div class="modal fade" id="deceasedImgModal" tabindex="-1" aria-labelledby="deceasedImgModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center p-0">
                <img src="" id="deceasedImgModalImg" class="img-fluid rounded" alt="Deceased Image" style="max-height:75vh;">
                <a id="deceasedImgModalDownload" href="#" download class="btn btn-primary mt-3">
                    <i class="bi bi-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.deceased-img-preview-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var imgUrl = this.getAttribute('data-img');
            var modalImg = document.getElementById('deceasedImgModalImg');
            var modalDownload = document.getElementById('deceasedImgModalDownload');
            modalImg.src = imgUrl;
            modalDownload.href = imgUrl;
            var modal = new bootstrap.Modal(document.getElementById('deceasedImgModal'));
            modal.show();
        });
    });
});
</script>
@endpush


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
    @if($booking->is_discount_beneficiary)
        <dt class="col-sm-5 text-secondary">Discount Amount</dt>
        <dd class="col-sm-7 text-success">
            - ₱{{ number_format($booking->discount_amount ?? 0, 2) }}
        </dd>
    @endif
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
                            <dt class="col-sm-5 text-secondary">Preferred Attire</dt>
                            <dd class="col-sm-7">{{ $details?->attire ?? '—' }}</dd>
                            <dt class="col-sm-5 text-secondary">Post Services</dt>
                            <dd class="col-sm-7">{{ $details?->post_services ?? '—' }}</dd>
                        </dl>
                        @endif

                        {{-- AGENT INFO --}}
                        <h5 class="mb-2"><i class="bi bi-person-badge"></i> Assigned Agent</h5>
                        @php
                            $assignedAgent = $booking->bookingAgent?->agentUser;
                        @endphp
                        @if($assignedAgent)
                            <div class="d-flex align-items-center gap-2 mb-4">
                                
                                <div>
                                    <div>{{ $assignedAgent->name }}</div>
                                    <div class="text-muted small">{{ $assignedAgent->email }}</div>
                                </div>
                            </div>
                        @else
                            <span class="text-muted mb-4 d-block">No agent assigned yet.</span>
                        @endif

                    {{-- SECTION: Cemetery & Plot Details --}}
{{-- SECTION: Cemetery & Plot Details --}}
<h5 class="mb-2 mt-4"><i class="bi bi-building"></i> Cemetery Details</h5>
<dl class="row mb-4">
    <dt class="col-sm-5 text-secondary">Cemetery Name</dt>
    <dd class="col-sm-7">
        {{ $cemeteryOwner?->name ?? 'Not specified' }}
    </dd>

    <dt class="col-sm-5 text-secondary">Cemetery Address</dt>
    <dd class="col-sm-7">
        {{ $plotCemetery?->address ?? 'Not specified' }}
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
                            @if($booking->status === 'completed')
                            <li class="list-group-item">
                                <strong>Completed On:</strong>
                                <span class="ms-1">{{ $booking->updated_at->format('M d, Y h:i A') }}</span>
                            </li>
                            @endif
                        </ul>

                        <a href="{{ route('client.dashboard') }}" class="btn btn-outline-secondary rounded-pill mt-3 px-4 py-2">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
