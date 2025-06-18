<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $booking->funeralHome->name ?? 'Funeral Parlor' }} - Service Details</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 13px; margin: 0; padding: 0; }
        .container { padding: 28px 32px 0 32px; }
        .header { text-align: center; margin-bottom: 18px; }
        h1, h2, h3 { margin: 0; padding: 0; }
        .parlor-logo { max-width: 110px; max-height: 110px; border-radius: 12px; margin-bottom: 8px;}
        .parlor-info { margin-bottom: 18px; }
        .parlor-name { font-size: 1.7rem; font-weight: bold; color: #153b5c; }
        .parlor-address, .parlor-contact { font-size: 1rem; color: #555; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 22px; }
        th, td { border: 1px solid #ddd; padding: 8px 6px; }
        th { background: #f5f5f5; text-align: left; }
        .section-title { margin: 16px 0 7px 0; font-weight: bold; font-size: 16px; border-bottom: 2px solid #153b5c; color: #153b5c; }
        .text-muted { color: #888; font-size: 12px; }
        .badge { padding: 3px 10px; border-radius: 7px; font-size: 12px; display: inline-block; }
        .bg-success { background: #d4edda; color: #155724; }
        .bg-warning { background: #fff3cd; color: #856404; }
        .bg-primary { background: #cfe2ff; color: #084298; }
        .bg-danger { background: #f8d7da; color: #721c24; }
        .bg-info { background: #d1ecf1; color: #0c5460; }
        .table-sm th, .table-sm td { padding: 4px 5px; font-size: 12px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 13px; }
        .mb-4 { margin-bottom: 20px; }
        .mt-1 { margin-top: 7px; }
        .mt-3 { margin-top: 16px; }
        .footer { text-align: center; font-size: 11px; color: #aaa; border-top: 1px dashed #eee; margin-top: 28px; padding: 5px 0 8px 0; }
    </style>
</head>
<body>
<div class="container">
    {{-- PARLOR BRANDING HEADER --}}
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
        <div class="text-muted" style="font-size:12px; margin-top: 6px;">
            Service Details Document
        </div>
    </div>

    {{-- CLIENT + PACKAGE INFO --}}
    <div class="mb-3">
        <div class="section-title">Client & Package Information</div>
        <table>
            <tr>
                <th>Client Name</th>
                <td>{{ $booking->client->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Package</th>
                <td>{{ $booking->package->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td>
                    ₱{{ number_format($booking->customizedPackage ? $booking->customizedPackage->custom_total_price : $booking->package->total_price, 2) }}
                </td>
            </tr>
            <tr>
                <th>Status</th>
                @php
                    $statuses = [
                        'pending'   => ['label' => 'Pending',   'class' => 'bg-warning'],
                        'assigned'  => ['label' => 'Agent Assigned', 'class' => 'bg-info'],
                        'confirmed' => ['label' => 'Confirmed', 'class' => 'bg-success'],
                        'ongoing'   => ['label' => 'Ongoing',   'class' => 'bg-primary'],
                        'done'      => ['label' => 'Completed', 'class' => 'bg-success'],
                        'declined'  => ['label' => 'Declined',  'class' => 'bg-danger'],
                    ];
                    $status = $statuses[$booking->status] ?? ['label' => ucfirst($booking->status), 'class' => 'bg-info'];
                @endphp
                <td>
                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                </td>
            </tr>
            <tr>
                <th>Requested On</th>
                <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
            </tr>
            @if($booking->status === 'done')
            <tr>
                <th>Completed On</th>
                <td>{{ $booking->updated_at->format('M d, Y h:i A') }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- PHASE 1 SERVICE --}}
    <div class="mb-3">
        <div class="section-title">Service Information</div>
        @php $details = json_decode($booking->details, true) ?? []; @endphp
        <table>
            <tr>
                <th>Deceased Name</th>
                <td>{{ $details['deceased_name'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Date of Death</th>
                <td>{{ $details['date_of_death'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Preferred Start Date</th>
                <td>{{ $details['preferred_schedule'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Additional Notes</th>
                <td>{{ $details['notes'] ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- PHASE 2 WAKE/BURIAL --}}
    <div class="mb-3">
        <div class="section-title">Wake & Burial Details</div>
        @php $phase2 = $booking->detail ?? null; @endphp
        <table>
            <tr>
                <th>Wake Start Date</th>
                <td>{{ $phase2?->wake_start_date ? \Carbon\Carbon::parse($phase2->wake_start_date)->format('M d, Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Wake End Date</th>
                <td>{{ $phase2?->wake_end_date ? \Carbon\Carbon::parse($phase2->wake_end_date)->format('M d, Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Burial/Interment Date</th>
                <td>{{ $phase2?->interment_cremation_date ? \Carbon\Carbon::parse($phase2->interment_cremation_date)->format('M d, Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Cemetery/Crematory</th>
                <td>{{ $phase2?->cemetery_or_crematory ?? '—' }}</td>
            </tr>
            <tr>
                <th>Plot Reserved?</th>
                <td>
                    @if(!is_null($phase2?->has_plot_reserved))
                        {{ $phase2->has_plot_reserved ? 'Yes' : 'No' }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <th>Preferred Attire</th>
                <td>{{ $phase2?->attire ?? '—' }}</td>
            </tr>
            <tr>
                <th>Post Services</th>
                <td>{{ $phase2?->post_services ?? '—' }}</td>
            </tr>
        </table>
    </div>

{{-- CUSTOMIZATION IF PRESENT --}}
@if($booking->customizedPackage)
<div class="mb-3">
    <div class="section-title">Package Customization</div>
    <div>
        <strong>Status:</strong>
        @php
            $cstat = $booking->customizedPackage->status;
            $cstatLabel = [
                'pending' => ['Pending Approval', 'bg-warning'],
                'approved'=> ['Approved', 'bg-success'],
                'denied'  => ['Denied', 'bg-danger'],
            ][$cstat] ?? ['Unknown', 'bg-info'];
        @endphp
        <span class="badge {{ $cstatLabel[1] }}">{{ $cstatLabel[0] }}</span>
        <span class="text-muted ms-2">
            Total Amount: ₱{{ number_format($booking->customizedPackage->custom_total_price,2) }}
        </span>
    </div>
    <table class="table table-sm table-bordered align-middle mt-1">
        <thead class="table-light">
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
        @foreach($booking->customizedPackage->items as $ci)
            <tr>
                <td>{{ $ci->inventoryItem->name ?? '-' }}</td>
                <td>{{ $ci->inventoryItem->category->name ?? '-' }}</td>
                <td>{{ $ci->inventoryItem->brand ?? '-' }}</td>
                <td>{{ $ci->quantity }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif


    {{-- AGENT --}}
    <div class="mb-3">
        <div class="section-title">Assigned Agent</div>
        @if($booking->agent)
        <table>
            <tr>
                <th>Name</th>
                <td>{{ $booking->agent->name }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $booking->agent->email }}</td>
            </tr>
        </table>
        @else
        <span class="text-muted">No agent assigned yet.</span>
        @endif
    </div>

    <div class="footer">
        <span>Powered by EternaLink — Document generated {{ now()->format('M d, Y h:i A') }}</span>
    </div>
</div>
</body>
</html>
