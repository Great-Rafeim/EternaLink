<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $booking->funeralHome->name ?? 'Funeral Parlor' }} - Booking PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 13px; margin: 0; padding: 0; }
        .container { padding: 30px 38px 0 38px; }
        .header { text-align: center; margin-bottom: 16px; }
        h1, h2, h3, h4, h5 { margin: 0; padding: 0; }
        .parlor-logo { max-width: 110px; max-height: 110px; border-radius: 10px; margin-bottom: 8px;}
        .parlor-info { margin-bottom: 10px; }
        .parlor-name { font-size: 1.4rem; font-weight: bold; color: #153b5c; }
        .parlor-address, .parlor-contact { font-size: 1rem; color: #555; }
        .section-title { margin: 22px 0 7px 0; font-weight: bold; font-size: 16px; border-bottom: 2px solid #153b5c; color: #153b5c; }
        .badge { padding: 2px 10px; border-radius: 7px; font-size: 12px; display: inline-block; }
        .bg-success { background: #d4edda; color: #155724; }
        .bg-warning { background: #fff3cd; color: #856404; }
        .bg-primary { background: #cfe2ff; color: #084298; }
        .bg-danger { background: #f8d7da; color: #721c24; }
        .bg-info { background: #d1ecf1; color: #0c5460; }
        .bg-secondary { background: #ececec; color: #333; }
        .table { border-collapse: collapse; width: 100%; margin-bottom: 16px; }
        th, td { border: 1px solid #ddd; padding: 7px 6px; }
        th { background: #f5f5f5; text-align: left; }
        ul, ol { padding-left: 16px; }
        .footer { text-align: center; font-size: 11px; color: #aaa; border-top: 1px dashed #eee; margin-top: 32px; padding: 5px 0 8px 0; }
        .text-muted { color: #888; font-size: 12px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 13px; }
        .mb-4 { margin-bottom: 22px; }
        .mt-2 { margin-top: 10px; }
        .mt-3 { margin-top: 16px; }
        .fw-bold { font-weight: bold; }
        .section-list { list-style: none; padding: 0; margin: 0 0 15px 0; }
        .list-header {
            background: #e9f1fa;
            color: #1e334c;
            font-weight: bold;
            border-bottom: 2px solid #b9cbe5;
        }
        .list-group-item { border-bottom: 1px solid #ddd; padding: 7px 6px; }
        .asset-row { background: #f5f8fc; }
        .to-be-decided-row { background: #f8f8f8; color: #888; font-style: italic; }
        .timeline {
            border-left: 3px solid #b0c4e9;
            margin: 20px 0 24px 0;
            padding-left: 20px;
        }
        .timeline-entry {
            position: relative;
            margin-bottom: 14px;
        }
        .timeline-dot {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid #fff;
            position: absolute;
            left: -27px;
            top: 6px;
        }
        .timeline-meta { font-size: 12px; color: #2a4b7c; margin-bottom: 2px; }
        .timeline-message {
            background: #f4f9ff;
            border-radius: 6px;
            padding: 6px 11px;
            margin-top: 2px;
            border-left: 3px solid #a5caee;
        }
    </style>
</head>
<body>
<div class="container">

    {{-- HEADER --}}
    <div class="header">
        @php
            $parlor = $booking->funeralHome;
            $imgPath = $parlor && $parlor->image ? public_path('storage/' . $parlor->image) : null;
        @endphp
        @if($imgPath && file_exists($imgPath))
            <img src="{{ $imgPath }}" class="parlor-logo" alt="Funeral Parlor Logo">
        @endif
        <div class="parlor-info">
            <div class="parlor-name">{{ $parlor->name ?? 'Funeral Parlor' }}</div>
            @if($parlor && $parlor->address)
                <div class="parlor-address">{{ $parlor->address }}</div>
            @endif
            @if($parlor && $parlor->contact_number)
                <div class="parlor-contact">Tel: {{ $parlor->contact_number }}</div>
            @endif
            @if($parlor && $parlor->contact_email)
                <div class="parlor-contact">Email: {{ $parlor->contact_email }}</div>
            @endif
        </div>
        <div class="text-muted">Booking Service Details</div>
    </div>

    {{-- STATUS --}}
    @php
        $statuses = [
            'pending'          => ['Pending', 'bg-warning'],
            'assigned'         => ['Agent Assigned', 'bg-info'],
            'confirmed'        => ['Confirmed', 'bg-success'],
            'in_progress'      => ['Client Filling Forms', 'bg-secondary'],
            'for_initial_review' => ['For Initial Review', 'bg-primary'],
            'for_review'       => ['For Final Review', 'bg-secondary'],
            'ongoing'          => ['Ongoing', 'bg-primary'],
            'done'             => ['Completed', 'bg-success'],
            'declined'         => ['Declined', 'bg-danger'],
        ];
        $status = $statuses[$booking->status] ?? [ucfirst(str_replace('_', ' ', $booking->status)), 'bg-info'];
        $useCustomized = $booking->customized_package_id && $booking->customizedPackage;
        $customized = $useCustomized ? $booking->customizedPackage : null;
        $packageItems = $customized
            ? $customized->items->map(function($ci) {
                return [
                    'item'     => $ci->inventoryItem->name ?? '-',
                    'category' => $ci->inventoryItem->category->name ?? '-',
                    'brand'    => $ci->inventoryItem->brand ?? '-',
                    'quantity' => $ci->quantity,
                    'is_asset' => $ci->inventoryItem->category->is_asset ?? false,
                    'category_id' => $ci->inventoryItem->category->id ?? null,
                ];
            })
            : $booking->package->items->map(function($item) {
                return [
                    'item'     => $item->name ?? '-',
                    'category' => $item->category->name ?? '-',
                    'brand'    => $item->brand ?? '-',
                    'quantity' => $item->pivot->quantity ?? 1,
                    'is_asset' => $item->category->is_asset ?? false,
                    'category_id' => $item->category->id ?? null,
                ];
            });
        $totalAmount = $customized
            ? $customized->custom_total_price
            : ($booking->package->total_price ?? (
                $booking->package->items->sum(fn($item) => ($item->pivot->quantity ?? 1) * ($item->selling_price ?? $item->price ?? 0))
            ));
        $details = $booking->detail;
        $assetCategories = $assetCategories ?? [];
        $assets = collect($packageItems)->filter(fn($pkg) => $pkg['is_asset'] ?? false);
        $consumables = collect($packageItems)->filter(fn($pkg) => !($pkg['is_asset'] ?? false));
        $assetCategoryIdsInItems = $assets->pluck('category_id')->unique()->toArray();
    @endphp

    <div class="mb-3">
        <span class="badge {{ $status[1] }}">{{ $status[0] }}</span>
        <span class="text-muted">Booking #{{ $booking->id }}</span>
    </div>

    {{-- PACKAGE HEADER: Name + Final Price --}}
    <div class="mb-2">
        <div class="section-title">Client & Package Info</div>
        <table class="table">
            <tr>
                <th>Client Name</th>
                <td>{{ $booking->client->name ?? '—' }}</td>
                <th>Package</th>
                <td>{{ $booking->package->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td>₱{{ number_format($totalAmount, 2) }}</td>
                <th>Status</th>
                <td><span class="badge {{ $status[1] }}">{{ $status[0] }}</span></td>
            </tr>
            <tr>
                <th>Requested On</th>
                <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
                <th>Completed On</th>
                <td>
                    @if($booking->status === 'completed')
                        {{ $booking->updated_at->format('M d, Y h:i A') }}
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- PACKAGE INCLUSIONS (styled as in show.blade) --}}
    <div class="mb-2">
        <div class="section-title">Package Inclusions</div>
        <table class="table">
            <tr class="list-header">
                <th>Item</th>
                <th>Category</th>
                <th>Brand</th>
                <th style="width:55px;">Qty</th>
            </tr>
            @forelse($consumables as $pkg)
                <tr>
                    <td>{{ $pkg['item'] }}</td>
                    <td>{{ $pkg['category'] }}</td>
                    <td>{{ $pkg['brand'] }}</td>
                    <td>{{ $pkg['quantity'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-muted">No consumable items included.</td>
                </tr>
            @endforelse

            {{-- Asset Items --}}
            @foreach($assets as $pkg)
                <tr class="asset-row">
                    <td>
                        {{ $pkg['item'] }}
                        <span class="badge bg-secondary">Asset</span>
                    </td>
                    <td>{{ $pkg['category'] }}</td>
                    <td>{{ $pkg['brand'] }}</td>
                    <td>{{ $pkg['quantity'] }}</td>
                </tr>
            @endforeach

            {{-- Asset categories not yet assigned --}}
            @foreach($assetCategories as $assetCategory)
                @if(!in_array($assetCategory->id, $assetCategoryIdsInItems))
                    <tr class="to-be-decided-row">
                        <td colspan="4">
                            Asset to be decided: <span class="fw-bold">{{ $assetCategory->name }}</span>
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    </div>

    {{-- SERVICE TIMELINE (updates) --}}
    @if(isset($serviceLogs) && $serviceLogs->count())
        <div class="mb-2">
            <div class="section-title">Service Updates</div>
            <div class="timeline">
                @foreach($serviceLogs as $log)
                    <div class="timeline-entry">
                        <div class="timeline-dot"></div>
                        <div class="timeline-meta">
                            {{ $log->user->name ?? 'Funeral Staff' }}
                            <span style="color:#888; margin-left:8px;">{{ $log->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="timeline-message">{!! nl2br(e($log->message)) !!}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

{{-- DECEASED DETAILS --}}
<div class="mb-2">
    <div class="section-title">Deceased Personal Details</div>
    <table class="table">
        <tr>
            <th>Full Name</th>
            <td>
                {{ collect([$details?->deceased_first_name, $details?->deceased_middle_name, $details?->deceased_last_name])->filter()->join(' ') ?: '—' }}
                @if($details?->deceased_nickname)
                    (“{{ $details->deceased_nickname }}”)
                @endif
            </td>
            <th>Residence</th>
            <td>{{ $details?->deceased_residence ?? '—' }}</td>
        </tr>
        <tr>
            <th>Sex</th>
            <td>
                @if(isset($details->deceased_sex))
                    {{ $details->deceased_sex === 'M' ? 'Male' : ($details->deceased_sex === 'F' ? 'Female' : $details->deceased_sex) }}
                @else
                    —
                @endif
            </td>
            <th>Civil Status</th>
            <td>{{ $details?->deceased_civil_status ?? '—' }}</td>
        </tr>
        <tr>
            <th>Birthday</th>
            <td>{{ $details?->deceased_birthday ? \Carbon\Carbon::parse($details->deceased_birthday)->format('M d, Y') : '—' }}</td>
            <th>Age</th>
            <td>{{ $details?->deceased_age ?? '—' }}</td>
        </tr>
        <tr>
            <th>Date of Death</th>
            <td>{{ $details?->deceased_date_of_death ? \Carbon\Carbon::parse($details->deceased_date_of_death)->format('M d, Y') : '—' }}</td>
            <th>Time of Death</th>
            <td>{{ $details?->deceased_time_of_death ?? '—' }}</td>
        </tr>
        <tr>
            <th>Cause of Death</th>
            <td>{{ $details?->deceased_cause_of_death ?? '—' }}</td>
            <th>Place of Death</th>
            <td>{{ $details?->deceased_place_of_death ?? '—' }}</td>
        </tr>
        <tr>
            <th>Religion</th>
            <td>{{ $details?->deceased_religion ?? '—' }}</td>
            <th>Occupation</th>
            <td>{{ $details?->deceased_occupation ?? '—' }}</td>
        </tr>
        <tr>
            <th>Citizenship</th>
            <td>{{ $details?->deceased_citizenship ?? '—' }}</td>
            <th>Father's Name</th>
            <td>
                {{ collect([$details?->deceased_father_first_name, $details?->deceased_father_middle_name, $details?->deceased_father_last_name])->filter()->join(' ') ?: '—' }}
            </td>
        </tr>
        <tr>
            <th>Mother's Maiden Name</th>
            <td>
                {{ collect([$details?->deceased_mother_first_name, $details?->deceased_mother_middle_name, $details?->deceased_mother_last_name])->filter()->join(' ') ?: '—' }}
            </td>
            <th>Corpse Disposal</th>
            <td>{{ $details?->corpse_disposal ?? '—' }}</td>
        </tr>
        {{-- SHOW DECEASED IMAGE --}}
        @if(!empty($details?->deceased_image))
        <tr>
            <th>Deceased Image</th>
            <td colspan="3">
                @php
                    $imgPath = public_path('storage/' . $details->deceased_image);
                @endphp
                @if(file_exists($imgPath))
                    <img src="{{ $imgPath }}" alt="Deceased Image" style="width:120px;height:120px;object-fit:cover;border-radius:10px;border:1px solid #bbb;">
                @else
                    <span class="text-muted">Image not found.</span>
                @endif
            </td>
        </tr>
        @endif
    </table>
</div>


    {{-- DOCUMENTS --}}
    <div class="mb-2">
        <div class="section-title">Documents</div>
        <table class="table">
            <tr>
                <th>Death Certificate Registration No.</th>
                <td>{{ $details?->death_cert_registration_no ?? '—' }}</td>
                <th>Death Cert. Released To</th>
                <td>{{ $details?->death_cert_released_to ?? '—' }}</td>
            </tr>
            <tr>
                <th>Death Cert. Release Date</th>
                <td>{{ $details?->death_cert_released_date ? \Carbon\Carbon::parse($details->death_cert_released_date)->format('M d, Y') : '—' }}</td>
                <th>Funeral Contract No.</th>
                <td>{{ $details?->funeral_contract_no ?? '—' }}</td>
            </tr>
            <tr>
                <th>Funeral Contract Released To</th>
                <td>{{ $details?->funeral_contract_released_to ?? '—' }}</td>
                <th>Funeral Contract Release Date</th>
                <td>{{ $details?->funeral_contract_released_date ? \Carbon\Carbon::parse($details->funeral_contract_released_date)->format('M d, Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Official Receipt No.</th>
                <td>{{ $details?->official_receipt_no ?? '—' }}</td>
                <th>Official Receipt Released To</th>
                <td>{{ $details?->official_receipt_released_to ?? '—' }}</td>
            </tr>
            <tr>
                <th>Official Receipt Release Date</th>
                <td>{{ $details?->official_receipt_released_date ? \Carbon\Carbon::parse($details->official_receipt_released_date)->format('M d, Y') : '—' }}</td>
                <th>Remarks</th>
                <td>{{ $details?->remarks ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- INFORMANT --}}
    <div class="mb-2">
        <div class="section-title">Informant Details</div>
        <table class="table">
            <tr>
                <th>Name</th>
                <td>{{ $details?->informant_name ?? '—' }}</td>
                <th>Age</th>
                <td>{{ $details?->informant_age ?? '—' }}</td>
            </tr>
            <tr>
                <th>Civil Status</th>
                <td>{{ $details?->informant_civil_status ?? '—' }}</td>
                <th>Relationship to Deceased</th>
                <td>{{ $details?->informant_relationship ?? '—' }}</td>
            </tr>
            <tr>
                <th>Contact No.</th>
                <td>{{ $details?->informant_contact_no ?? '—' }}</td>
                <th>Address</th>
                <td>{{ $details?->informant_address ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- SERVICE & PAYMENT --}}
    <div class="mb-2">
        <div class="section-title">Service & Payment</div>
        <table class="table">
            <tr>
                <th>Service</th>
                <td>{{ $details?->service ?? $booking->package->name ?? '—' }}</td>
                <th>Package Amount</th>
                <td>₱{{ number_format($details?->amount ?? $totalAmount, 2) }}</td>
            </tr>
            <tr>
                <th>Other Fee</th>
                <td>
                    @if($details?->other_fee)
                        ₱{{ number_format($details->other_fee, 2) }}
                    @else
                        <span class="text-warning">Not set</span>
                    @endif
                </td>
                <th>Deposit</th>
                <td>{{ $details?->deposit ?? '—' }}</td>
            </tr>
            <tr>
                <th>CSWD</th>
                <td>{{ $details?->cswd ?? '—' }}</td>
                <th>DSWD</th>
                <td>{{ $details?->dswd ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- CERTIFICATION --}}
    <div class="mb-2">
        <div class="section-title">Certification</div>
        <table class="table">
            <tr>
                <th>Certifier Name</th>
                <td>{{ $details?->certifier_name ?? '—' }}</td>
                <th>Relationship to Deceased</th>
                <td>{{ $details?->certifier_relationship ?? '—' }}</td>
            </tr>
            <tr>
                <th>Residence</th>
                <td>{{ $details?->certifier_residence ?? '—' }}</td>
                <th>Amount in Words</th>
                <td>{{ $details?->certifier_amount ?? '—' }}</td>
            </tr>
            <tr>
                <th>Signature</th>
                <td colspan="3">
                    @if(!empty($details?->certifier_signature_image))
                        <img src="{{ $details->certifier_signature_image }}" alt="Signature" style="max-width:160px">
                    @else
                        {{ $details?->certifier_signature ?? '—' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- PHASE 2: Wake, Burial, Customization --}}
    <div class="mb-2">
        <div class="section-title">Wake, Burial & Customization</div>
        <table class="table">
            <tr>
                <th>Wake Start Date</th>
                <td>{{ $details?->wake_start_date ? \Carbon\Carbon::parse($details->wake_start_date)->format('M d, Y') : '—' }}</td>
                <th>Wake End Date</th>
                <td>{{ $details?->wake_end_date ? \Carbon\Carbon::parse($details->wake_end_date)->format('M d, Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Burial/Interment Date</th>
                <td>{{ $details?->interment_cremation_date ? \Carbon\Carbon::parse($details->interment_cremation_date)->format('M d, Y') : '—' }}</td>
                <th>Burial/Interment Time</th>
                <td>{{ $details?->interment_cremation_time ?? '—' }}</td>
            </tr>
            <tr>
                <th>Cemetery / Crematory</th>
                <td>{{ $details?->cemetery_or_crematory ?? '—' }}</td>
                <th>Plot Reserved?</th>
                <td>
                    @if(!is_null($details?->has_plot_reserved))
                        <span class="badge bg-{{ $details->has_plot_reserved ? 'success' : 'secondary' }}">
                            {{ $details->has_plot_reserved ? 'Yes' : 'No' }}
                        </span>
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <th>Preferred Attire</th>
                <td>{{ $details?->attire ?? '—' }}</td>
                <th>Post Services</th>
                <td>{{ $details?->post_services ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- AGENT --}}
    <div class="mb-2">
        <div class="section-title">Assigned Agent</div>
        @php $assignedAgent = $booking->bookingAgent?->agentUser; @endphp
        @if($assignedAgent)
        <table class="table">
            <tr>
                <th>Name</th>
                <td>{{ $assignedAgent->name }}</td>
                <th>Email</th>
                <td>{{ $assignedAgent->email }}</td>
            </tr>
        </table>
        @else
            <span class="text-muted">No agent assigned yet.</span>
        @endif
    </div>

    {{-- CEMETERY & PLOT --}}
    @php
        $cemetery = $booking->cemeteryBooking->cemetery ?? null;
        $plot = $booking->cemeteryBooking->plot ?? null;
    @endphp
    <div class="mb-2">
        <div class="section-title">Cemetery & Plot Details</div>
        <table class="table">
            <tr>
                <th>Cemetery Name</th>
                <td>{{ $cemetery?->user->name ?? 'Not specified' }}</td>
                <th>Cemetery Address</th>
                <td>{{ $cemetery?->address ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <th>Plot Number</th>
                <td>
                    @if($plot)
                        {{ $plot->plot_number ?? 'Not specified' }}
                    @else
                        <span class="text-warning">Waiting for assignment</span>
                    @endif
                </td>
                <th>Section</th>
                <td>
                    @if($plot)
                        {{ $plot->section ?? 'Not specified' }}
                    @else
                        <span class="text-warning">Waiting for assignment</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Block</th>
                <td colspan="3">
                    @if($plot)
                        {{ $plot->block ?? 'Not specified' }}
                    @else
                        <span class="text-warning">Waiting for assignment</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- TIMELINE --}}
    <div class="mb-2">
        <div class="section-title">Booking Timeline</div>
        <table class="table">
            <tr>
                <th>Requested On</th>
                <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
                <th>Last Updated</th>
                <td>{{ $booking->updated_at->format('M d, Y h:i A') }}</td>
            </tr>
            @if($booking->status === 'completed')
            <tr>
                <th>Completed On</th>
                <td colspan="3">{{ $booking->updated_at->format('M d, Y h:i A') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        Powered by EternaLink &mdash; Document generated {{ now()->format('M d, Y h:i A') }}
    </div>
</div>
</body>
</html>
