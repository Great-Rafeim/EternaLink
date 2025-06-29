<x-layouts.funeral>
    <style>
        .timeline {
            position: relative;
            padding-left: 1.7rem;
            margin-bottom: 0;
        }
        .timeline-item {
            border-left: 2px solid #0d6efd33;
            margin-left: 0.5rem;
            position: relative;
            padding-left: 1.5rem;
        }
        .timeline-dot {
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 50%;
            border: 2px solid #fff;
            background: #0d6efd;
            left: -1.09rem;
            top: 0.5rem;
            position: absolute;
            z-index: 2;
        }
        .timeline-item:last-child {
            border-left: 2px solid transparent;
        }
        .timeline-message {
            background: #f5faff;
            border-radius: 0.4rem;
            box-shadow: 0 1px 8px #0d6efd0a;
            padding: 0.7rem 1rem;
            margin-top: 0.2rem;
            margin-bottom: 0;
            font-size: 1.06rem;
        }
        .timeline-meta {
            font-size: 0.98rem;
        }
        /* Unify card width & flatten look */
        .section-card {
            border-radius: 0.5rem !important;
            box-shadow: 0 1px 8px #0d6efd10;
            border: 1px solid #e7eaf0;
            max-width: 900px;
            margin: 0 auto 2rem auto;
        }
        .section-card .card-header,
        .section-card .modal-header {
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        .section-card .card-footer,
        .section-card .modal-footer {
            border-radius: 0 0 0.5rem 0.5rem !important;
        }
        .section-card .modal-content {
            border-radius: 0.5rem !important;
        }
        /* Match modal dialog width to cards */
        .modal-dialog {
            max-width: 900px;
        }
        .btn,
        .form-control,
        .form-select {
            border-radius: 0.3rem !important;
        }
        .rounded-pill,
        .rounded-4,
        .rounded-top-4,
        .rounded-bottom-4 {
            border-radius: 0.3rem !important;
        }
        /* Adjust alert and badges for flatter look */
        .alert, .badge {
            border-radius: 0.2rem !important;
        }
    </style>

    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex align-items-center mb-4 gap-3" style="max-width: 900px; margin: 0 auto;">
            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
                Back to Booking
            </a>
            <h2 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                <i class="bi bi-gear"></i>
                Manage Service
            </h2>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show section-card" role="alert" style="padding: .9rem 1.2rem;">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show section-card" role="alert" style="padding: .9rem 1.2rem;">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Service Timeline --}}
        <div class="card section-card mb-4 animate__animated animate__fadeIn">
            <div class="card-header bg-gradient bg-primary text-white">
                <i class="bi bi-clock-history me-2"></i>
                <span class="fw-semibold fs-5">Service Timeline</span>
            </div>
            <div class="card-body pb-1 px-0">
                @if($serviceLogs->isEmpty())
                    <div class="text-center text-muted my-4">
                        <i class="bi bi-info-circle fs-3"></i>
                        <div>No updates posted yet.</div>
                    </div>
                @else
                    <div class="timeline mt-3">
                        @foreach($serviceLogs as $log)
                            <div class="timeline-item mb-4 pb-1">
                                <span class="timeline-dot"></span>
                                <div class="timeline-meta mb-1">
                                    <i class="bi bi-person-circle text-primary me-1"></i>
                                    <span class="fw-semibold text-primary">{{ $log->user->name ?? 'Funeral Staff' }}</span>
                                    <span class="text-muted ms-2"><i class="bi bi-clock"></i> {{ $log->created_at->format('M d, Y h:i A') }}</span>
                                </div>
                                <div class="timeline-message">
                                    {!! nl2br(e($log->message)) !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Post New Update --}}
            @if($booking->status === \App\Models\Booking::STATUS_ONGOING || $booking->status === \App\Models\Booking::STATUS_COMPLETED)
                <div class="card-body py-4 border-0">
                    <form action="{{ route('funeral.bookings.manage-service.post-update', $booking->id) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-12 col-md-10">
                            <label for="service_update" class="form-label fw-semibold text-primary">
                                <i class="bi bi-pencil-square"></i> Post New Update
                            </label>
                            <textarea name="message" id="service_update" rows="2"
                                class="form-control form-control-lg shadow-sm @error('message') is-invalid @enderror"
                                placeholder="Write update (e.g., 'Body retrieved from hospital. Wake started...')" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg mt-2 mt-md-0 shadow-sm">
                                <i class="bi bi-send"></i> Send Update
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        {{-- End Service Button --}}
        @if($booking->status === \App\Models\Booking::STATUS_ONGOING)
            <div class="section-card text-start mb-4" style="max-width:900px;">
                <button type="button" class="btn btn-danger btn-lg px-5 shadow" data-bs-toggle="modal" data-bs-target="#endServiceModal">
                    <i class="bi bi-flag-fill"></i> End Service
                </button>
            </div>
        @endif

        {{-- End Service Confirmation Modal --}}
        <div class="modal fade" id="endServiceModal" tabindex="-1" aria-labelledby="endServiceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('funeral.bookings.manage-service.end', $booking->id) }}" class="modal-content section-card border-0">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="endServiceModalLabel">
                            <i class="bi bi-flag-fill"></i> Confirm End Service
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fs-5">
                            Are you sure you want to <strong>End this Service</strong>?<br>
                            <span class="text-danger fw-bold">This action cannot be undone.</span> The client will be notified.
                        </p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4">Yes, End Service</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ASSET ASSIGNMENT SECTION --}}
        @if($booking->status === \App\Models\Booking::STATUS_ONGOING || $booking->status === \App\Models\Booking::STATUS_COMPLETED)
            <div class="card section-card mb-4 animate__animated animate__fadeIn">
                <div class="card-header bg-gradient bg-info text-white d-flex align-items-center gap-2">
                    <i class="bi bi-truck-front"></i>
                    <span class="fw-semibold fs-6">Assign Bookable Assets</span>
                </div>
                <div class="card-body pb-2">
                    <form action="{{ route('funeral.bookings.assign-assets', $booking->id) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        @foreach($assetCategories as $cat)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label class="form-label fw-semibold d-flex align-items-center justify-content-between">
                                    <span>
                                        {{ $cat->name }}
                                        @if(isset($assignedAssets[$cat->id]))
                                            <span class="badge bg-success ms-1">
                                                Assigned: {{ $assignedAssets[$cat->id]->name ?? '' }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark ms-1">Required</span>
                                        @endif
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-info ms-2 py-1 px-2"
                                        data-bs-toggle="modal" data-bs-target="#assetScheduleModal{{ $cat->id }}">
                                        <i class="bi bi-calendar2-week"></i> Schedule
                                    </button>
                                </label>
                                <select name="assets[{{ $cat->id }}]" class="form-select" required>
                                    <option value="">-- Select Asset --</option>
                                    @foreach($availableAssets[$cat->id] ?? [] as $asset)
                                        @php
                                            $isBorrowed = $asset->status === 'borrowed_from_partner';
                                            $borrowedStart = $isBorrowed ? \Carbon\Carbon::parse($asset->borrowed_start) : null;
                                            $borrowedEnd = $isBorrowed ? \Carbon\Carbon::parse($asset->borrowed_end) : null;
                                            $serviceStart = \Carbon\Carbon::parse($serviceStart ?? now());
                                            $serviceEnd = \Carbon\Carbon::parse($serviceEnd ?? now());
                                            $canAssign = true;
                                            $borrowedWarning = null;
                                            if ($isBorrowed) {
                                                if ($serviceStart->lt($borrowedStart) || $serviceEnd->gt($borrowedEnd)) {
                                                    $canAssign = false;
                                                    $borrowedWarning = "Unavailable: Borrowed asset only assignable between {$borrowedStart->format('Y-m-d')} and {$borrowedEnd->format('Y-m-d')}";
                                                }
                                            }
                                            $borrowedPeriod = $isBorrowed
                                                ? $borrowedStart->format('Y-m-d') . ' to ' . $borrowedEnd->format('Y-m-d')
                                                : null;
                                        @endphp
                                        <option value="{{ $asset->id }}"
                                            {{ (isset($assignedAssets[$cat->id]) && $assignedAssets[$cat->id]->id == $asset->id) ? 'selected' : '' }}
                                            @if(!$canAssign) disabled style="color: #aaa; background: #f8d7da;" @endif
                                        >
                                            {{ $asset->name }}
                                            @if($asset->brand) - {{ $asset->brand }}@endif
                                            ({{ ucfirst(str_replace('_',' ',$asset->status)) }})
                                            @if($borrowedPeriod)
                                                [Borrowed: {{ $borrowedPeriod }}]
                                            @endif
                                            @if(!$canAssign)
                                                — {{ $borrowedWarning }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @php
                                    $hasUnassignableBorrowed = collect($availableAssets[$cat->id] ?? [])->filter(function($asset) use ($serviceStart, $serviceEnd) {
                                        if($asset->status !== 'borrowed_from_partner') return false;
                                        $start = \Carbon\Carbon::parse($asset->borrowed_start);
                                        $end = \Carbon\Carbon::parse($asset->borrowed_end);
                                        return $serviceStart->lt($start) || $serviceEnd->gt($end);
                                    })->count();
                                @endphp
                                @if($hasUnassignableBorrowed)
                                    <div class="small text-danger mt-1">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Some borrowed assets are not available for your booking window.
                                    </div>
                                @endif
                            </div>

                            {{-- ASSET SCHEDULE MODAL FOR CATEGORY --}}
                            <div class="modal fade" id="assetScheduleModal{{ $cat->id }}" tabindex="-1" aria-labelledby="assetScheduleLabel{{ $cat->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content section-card">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title" id="assetScheduleLabel{{ $cat->id }}">
                                                <i class="bi bi-calendar2-week"></i> Asset Schedule: {{ $cat->name }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle">
                                                    <thead class="table-info">
                                                        <tr>
                                                            <th>Asset Name</th>
                                                            <th>Origin</th>
                                                            <th>Booking ID</th>
                                                            <th>Reserved Start</th>
                                                            <th>Reserved End</th>
                                                            <th>Status</th>
                                                            <th>Borrowed Window</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $categoryAssetIds = $availableAssets[$cat->id]->pluck('id')->all();
                                                            $allCategoryAssets = \App\Models\InventoryItem::where('inventory_category_id', $cat->id)->get();
                                                            $reservations = \App\Models\AssetReservation::whereIn('inventory_item_id', $allCategoryAssets->pluck('id'))
                                                                ->where('reserved_end', '>=', now())
                                                                ->whereIn('status', ['reserved', 'in_use']) 
                                                                ->orderBy('reserved_start')
                                                                ->get();
                                                        @endphp
                                                        @forelse($reservations as $res)
                                                            @php
                                                                $item = $res->inventoryItem;
                                                                $isBorrowed = $item && $item->status === 'borrowed_from_partner';
                                                                $borrowedWindow = '';
                                                                if ($isBorrowed) {
                                                                    $borrowedWindow = \Carbon\Carbon::parse($item->borrowed_start)->format('M d, Y') . ' — ' .
                                                                        \Carbon\Carbon::parse($item->borrowed_end)->format('M d, Y');
                                                                }
                                                            @endphp
                                                            <tr
                                                                @if($res->booking_id == $booking->id)
                                                                    class="table-success"
                                                                @elseif($res->status == 'reserved' || $res->status == 'in_use')
                                                                    class="table-warning"
                                                                @endif
                                                            >
                                                                <td>
                                                                    {{ $item->name ?? 'N/A' }}
                                                                    @if($item && $item->brand)
                                                                        <span class="text-muted small">({{ $item->brand }})</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($isBorrowed)
                                                                        <span class="badge bg-secondary" title="Borrowed from Partner">Borrowed</span>
                                                                    @else
                                                                        <span class="badge bg-primary" title="Owned by your parlor">Local</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($res->booking_id)
                                                                        <span class="badge bg-primary">#{{ $res->booking_id }}</span>
                                                                    @elseif($res->shared_with_partner_id)
                                                                        <span class="badge bg-secondary">Partner Share</span>
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ \Carbon\Carbon::parse($res->reserved_start)->format('M d, Y h:i A') }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($res->reserved_end)->format('M d, Y h:i A') }}</td>
                                                                <td>
                                                                    <span class="badge bg-{{ $res->status == 'in_use' ? 'danger' : ($res->status == 'reserved' ? 'warning text-dark' : 'secondary') }}">
                                                                        {{ ucfirst($res->status) }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    @if($isBorrowed)
                                                                        <span class="badge bg-info text-dark">{{ $borrowedWindow }}</span>
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="text-center text-muted">No reservations found for this category.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="small mt-2 text-muted">
                                                <span class="badge bg-success">&nbsp;</span> This booking &nbsp;
                                                <span class="badge bg-warning text-dark">&nbsp;</span> Reserved or In Use &nbsp;
                                                <span class="badge bg-secondary">&nbsp;</span> Borrowed from Partner &nbsp;
                                                <span class="badge bg-primary">&nbsp;</span> Local Asset
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="bi bi-x-lg"></i> Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-info px-4 shadow-sm">
                                <i class="bi bi-save"></i> Save Asset Assignments
                            </button>
                        </div>
                    </form>
                    <div class="alert alert-secondary mt-2 small">
                        <i class="bi bi-info-circle"></i>
                        Only assets that are currently available, or borrowed by your funeral parlor and still within their borrowed window, will be listed. Assigning or re-assigning assets will update their reservation for this booking.
                        You can view asset usage schedules before assigning.
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.funeral>
