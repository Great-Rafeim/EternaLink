<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $booking->funeralHome->name ?? 'Funeral Parlor' }} - Booking PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 13px; margin: 0; padding: 0; }
        .container { padding: 30px 38px 0 38px; }
        .header { text-align: center; margin-bottom: 16px; }
        .parlor-logo { max-width: 110px; max-height: 110px; border-radius: 10px; margin-bottom: 8px;}
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
        .footer { text-align: center; font-size: 11px; color: #aaa; border-top: 1px dashed #eee; margin-top: 32px; padding: 5px 0 8px 0; }
        .text-muted { color: #888; font-size: 12px; }
        .fw-bold { font-weight: bold; }
        .asset-row { background: #f5f8fc; }
        .to-be-decided-row { background: #f8f8f8; color: #888; font-style: italic; }
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
        <div style="font-size:1.4rem; font-weight: bold; color: #153b5c;">{{ $parlor->name ?? 'Funeral Parlor' }}</div>
        @if($parlor && $parlor->address)
            <div style="font-size:1rem; color:#555;">{{ $parlor->address }}</div>
        @endif
        @if($parlor && $parlor->contact_number)
            <div style="font-size:1rem; color:#555;">Tel: {{ $parlor->contact_number }}</div>
        @endif
        @if($parlor && $parlor->contact_email)
            <div style="font-size:1rem; color:#555;">Email: {{ $parlor->contact_email }}</div>
        @endif
        <div class="text-muted">Booking Service Details</div>
    </div>

    {{-- STATUS --}}
    @php
        $statuses = [
            'pending' => ['Pending', 'bg-warning'],
            'assigned' => ['Agent Assigned', 'bg-info'],
            'confirmed' => ['Confirmed', 'bg-success'],
            'in_progress' => ['Client Filling Forms', 'bg-secondary'],
            'for_initial_review' => ['For Initial Review', 'bg-primary'],
            'for_review' => ['For Final Review', 'bg-secondary'],
            'ongoing' => ['Ongoing', 'bg-primary'],
            'done' => ['Completed', 'bg-success'],
            'declined' => ['Declined', 'bg-danger'],
            'completed' => ['Completed', 'bg-success'],
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
                    'unit_price' => $ci->unit_price ?? 0,
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
                    'unit_price' => $item->selling_price ?? $item->price ?? 0,
                ];
            });
        $totalAmount = $customized
            ? $customized->custom_total_price
            : ($booking->package->total_price ?? (
                $booking->package->items->sum(fn($item) => ($item->pivot->quantity ?? 1) * ($item->selling_price ?? $item->price ?? 0))
            ));
        $details = $booking->detail;
        $assetCategories = $assetCategories ?? [];
        $assetCategoryPrices = $assetCategoryPrices ?? [];
        $assets = collect($packageItems)->filter(fn($pkg) => $pkg['is_asset'] ?? false);
        $consumables = collect($packageItems)->filter(fn($pkg) => !($pkg['is_asset'] ?? false));
        $assetCategoryIdsInItems = $assets->pluck('category_id')->unique()->toArray();
    @endphp

    <div class="mb-3">
        <span class="badge {{ $status[1] }}">{{ $status[0] }}</span>
        <span class="text-muted">Booking #{{ $booking->id }}</span>
    </div>

    {{-- PACKAGE HEADER --}}
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

 {{-- PACKAGE INCLUSIONS --}}
<div class="mb-2">
    <div class="section-title">Package Inclusions</div>
    <table class="table">
        <tr>
            <th>Item</th>
            <th>Category</th>
            <th>Brand</th>
            <th style="width:60px;">Qty</th>
            <th style="width:110px;">Unit Price</th>
            <th style="width:130px;">Subtotal</th>
        </tr>
        @php
            $consumableTotal = 0;
        @endphp
        @forelse($consumables as $pkg)
            @php
                $unit = floatval($pkg['unit_price'] ?? 0);
                $qty = intval($pkg['quantity']);
                $subtotal = $unit * $qty;
                $consumableTotal += $subtotal;
            @endphp
            <tr>
                <td>{{ $pkg['item'] }}</td>
                <td>{{ $pkg['category'] }}</td>
                <td>{{ $pkg['brand'] }}</td>
                <td>{{ $qty }}</td>
                <td>₱{{ number_format($unit, 2) }}</td>
                <td>₱{{ number_format($subtotal, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-muted">No consumable items included.</td>
            </tr>
        @endforelse
        @if($consumableTotal > 0)
            <tr style="background:#f9f9f9;">
                <td colspan="5" class="fw-bold text-end">Consumables Total</td>
                <td class="fw-bold">₱{{ number_format($consumableTotal, 2) }}</td>
            </tr>
        @endif
    </table>
</div>

{{-- ASSET CATEGORIES --}}
<div class="mb-2">
    <div class="section-title">Bookable Assets/Items</div>
    <table class="table">
        <tr>
            <th>Asset Item / Category</th>
            <th>Category Price</th>
        </tr>
        @php $assetTotal = 0; @endphp
        @foreach($assets as $pkg)
            @php
                $catId = $pkg['category_id'];
                $catPrice = $assetCategoryPrices[$catId] ?? 0;
                $assetTotal += $catPrice;
            @endphp
            <tr class="asset-row">
                <td>
                    {{ $pkg['item'] }}
                    <span class="badge bg-secondary">Asset</span>
                </td>
                <td>₱{{ number_format($catPrice, 2) }}</td>
            </tr>
        @endforeach
        @foreach($assetCategories as $assetCategory)
            @if(!in_array($assetCategory->id, $assetCategoryIdsInItems))
                @php
                    $catPrice = $assetCategoryPrices[$assetCategory->id] ?? 0;
                    $assetTotal += $catPrice;
                @endphp
                <tr class="to-be-decided-row">
                    <td>
                        <span class="fw-bold">{{ $assetCategory->name }}</span>
                        <span class="badge bg-secondary">Asset</span>
                    </td>
                    <td>₱{{ number_format($catPrice, 2) }}</td>
                </tr>
            @endif
        @endforeach
        @if(($assetCategories ?? collect())->count())
            <tr style="background:#f9f9f9;">
                <td class="fw-bold text-end">Assets Total</td>
                <td class="fw-bold">₱{{ number_format($assetTotal, 2) }}</td>
            </tr>
        @endif
    </table>
</div>

{{-- OVERALL TOTAL, DISCOUNT, VAT, GRAND TOTAL --}}
@php
    // Sum up package items, asset total, etc. Use package custom price or fallback.
    $preDiscountAmount = $customized
        ? $customized->custom_total_price
        : ($booking->package->total_price ?? ($consumableTotal + $assetTotal));

    $discount = $booking->is_discount_beneficiary ? ($booking->discount_amount ?? 0) : 0;
    $netAmount = max($preDiscountAmount - $discount, 0);

    // VAT is always based on the net amount
    $vatRate = 0.12;
    $vat = $netAmount * $vatRate;
    $amountExVat = $netAmount - $vat;

    // Add any other fees (from booking details, e.g. delivery, etc.)
    $otherFee = isset($details->other_fee) ? floatval($details->other_fee) : 0;
    $grandTotal = $netAmount + $otherFee;
@endphp

<div class="mb-2">
    <div class="section-title">Summary of Charges</div>
    <table class="table" style="width: 360px; float: right; margin-bottom: 0;">
        <tr>
            <th style="width: 180px;">Subtotal</th>
            <td style="text-align:right;">₱{{ number_format($preDiscountAmount, 2) }}</td>
        </tr>
        @if($discount > 0)
        <tr>
            <th>Discount</th>
            <td style="text-align:right;">- ₱{{ number_format($discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <th>Net Amount (VAT Inclusive)</th>
            <td style="text-align:right;">₱{{ number_format($netAmount, 2) }}</td>
        </tr>
        <tr>
            <th>VATable Sales</th>
            <td style="text-align:right;">₱{{ number_format($amountExVat, 2) }}</td>
        </tr>
        <tr>
            <th>VAT (12%)</th>
            <td style="text-align:right;">₱{{ number_format($vat, 2) }}</td>
        </tr>
        @if($otherFee > 0)
        <tr>
            <th>Other Fee</th>
            <td style="text-align:right;">₱{{ number_format($otherFee, 2) }}</td>
        </tr>
        @endif
        <tr>
            <th class="fw-bold" style="font-size: 15px;">Grand Total</th>
            <td class="fw-bold" style="font-size: 15px; text-align:right;">
                ₱{{ number_format($grandTotal, 2) }}
            </td>
        </tr>
    </table>
    <div style="clear:both;"></div>
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
            @if($booking->is_discount_beneficiary)
            <tr>
                <th>Discount Amount</th>
                <td colspan="3" class="text-success">
                    - ₱{{ number_format($booking->discount_amount ?? 0, 2) }}
                </td>
            </tr>
            @endif
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
            <tr>
                <th>Remarks</th>
                <td colspan="3">{{ $details?->remarks ?? '—' }}</td>
            </tr>
        </table>
    </div>

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
                <th>Preferred Attire</th>
                <td>{{ $details?->attire ?? '—' }}</td>
            </tr>
            <tr>
                <th>Post Services</th>
                <td colspan="3">{{ $details?->post_services ?? '—' }}</td>
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
    <div class="mb-2">
        <div class="section-title">Cemetery & Plot Details</div>
        <table class="table">
            <tr>
                <th>Cemetery Name</th>
                <td>{{ $cemeteryOwner?->name ?? 'Not specified' }}</td>
                <th>Cemetery Address</th>
                <td>{{ $plotCemetery?->address ?? 'Not specified' }}</td>
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

    {{-- SERVICE LOGS / UPDATES --}}
    @if(isset($serviceLogs) && $serviceLogs->count())
        <div class="mb-2">
            <div class="section-title">Service Updates</div>
            <table class="table">
                <tr>
                    <th>User</th>
                    <th>Timestamp</th>
                    <th>Message</th>
                </tr>
                @foreach($serviceLogs as $log)
                    <tr>
                        <td>{{ $log->user->name ?? 'Funeral Staff' }}</td>
                        <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        <td>{!! nl2br(e($log->message)) !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="footer">
        Powered by EternaLink &mdash; Document generated {{ now()->format('M d, Y h:i A') }}
    </div>
</div>
</body>
</html>
