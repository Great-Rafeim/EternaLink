<x-layouts.funeral>
    <div class="container py-5">
        <h2 class="fw-bold mb-4 text-primary">
            <i class="bi bi-calendar-event me-2"></i> Bookable Asset Reservations
        </h2>

        {{-- Filters --}}
        <form method="GET" class="row mb-4 g-2 align-items-end">
            <div class="col-md-3">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="active" {{ request('status', 'active') == 'active' ? 'selected' : '' }}>Active (Reserved/In Use)</option>
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                    <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Category</label>
                <select name="category" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                            @if($cat->is_asset) [Asset: {{ ucfirst($cat->reservation_mode) }}] @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Asset</label>
                <select name="asset" class="form-select">
                    <option value="">All</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ request('asset') == $asset->id ? 'selected' : '' }}>
                            {{ $asset->name }}
                            @if($asset->shareable) <span class="badge bg-info text-dark ms-1">Shareable</span>@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>

        {{-- Reservation Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Asset</th>
                        <th>Category / Mode</th>
                        <th>Shareable</th>
                        <th>Reserved For</th>
                        <th>Booking</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Managed By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reservations as $r)
                    @php
                        $category = optional($r->inventoryItem)->category;
                        $isAsset = $category && $category->is_asset;
                        $reservationMode = $category ? $category->reservation_mode : null;
                        $shareable = optional($r->inventoryItem)->shareable;
                        $now = now();

                        $isRequester = (
                            (auth()->id() === optional($r->sharedWithPartner)->id) ||
                            ($r->booking && $r->booking->client && auth()->id() === $r->booking->client->id)
                        );
                        $isProvider = (
                            auth()->id() === optional($r->creator)->id
                        );

                        $start = $r->reserved_start;
                        $end = $r->reserved_end;

                        $canCancel = $r->status === 'reserved' && $now->lt($start);
                        $canReturnEarly = $isRequester && $r->status === 'in_use' && $now->lt($end);
                        $canProviderCancel = $isProvider && $r->status === 'reserved' && $now->lt($start);
                        // Mark as Received ONLY if status is 'completed'
                        $canMarkAsReceived = $isProvider && $r->status === 'for_return' && $now->gte($r->reserved_end);
                    @endphp
                    <tr>
                        <td>
                            {{ $r->inventoryItem->name ?? 'N/A' }}
                            @if($shareable)
                                <span class="badge bg-info text-dark ms-1">Shareable</span>
                            @endif
                        </td>
                        <td>
                            {{ $category->name ?? '-' }}
                            @if($isAsset)
                                <span class="badge bg-primary ms-1">{{ ucfirst($reservationMode) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($shareable)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            @if($r->booking && $r->booking->client)
                                <div>{{ $r->booking->client->name }}</div>
                                <div class="text-muted small">{{ $r->booking->client->email }}</div>
                            @elseif($r->sharedWithPartner)
                                <div>
                                    <span class="badge bg-info text-dark mb-1">
                                        <i class="bi bi-arrow-left-right me-1"></i>
                                        Shared with: {{ $r->sharedWithPartner->name }}
                                    </span>
                                    <div class="text-muted small">
                                        {{ $r->sharedWithPartner->email }}
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($r->booking)
                                <a href="{{ route('funeral.bookings.show', $r->booking_id) }}" target="_blank">
                                    #{{ $r->booking_id }}
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            {{ $r->reserved_start ? $r->reserved_start->format('Y-m-d H:i') : '-' }}
                            @if($reservationMode == 'single_event')
                                <span class="badge bg-info ms-1">Single Event</span>
                            @endif
                        </td>
                        <td>{{ $r->reserved_end ? $r->reserved_end->format('Y-m-d H:i') : '-' }}</td>
                        <td>
<span class="badge 
    {{ 
        $r->status == 'reserved' ? 'bg-warning' : 
        ($r->status == 'in_use' ? 'bg-info' :
        ($r->status == 'for_return' ? 'bg-primary' :
        ($r->status == 'completed' ? 'bg-success' :
        ($r->status == 'closed' ? 'bg-dark text-white' :
        ($r->status == 'available' ? 'bg-primary' : 'bg-secondary')))))
    }}">
    {{ $r->status == 'for_return' ? 'For Return' : ucfirst(str_replace('_', ' ', $r->status)) }}
</span>

                        </td>
                        <td>{{ $r->creator->name ?? 'N/A' }}</td>
<td>
    {{-- CLIENT BOOKING: Funeral Parlor Manual Status --}}
    @if($r->booking_id && !$r->shared_with_partner_id)
        @php
            // Funeral home admin check (customize as needed)
            $canManage = auth()->user()->id === $r->inventoryItem->funeral_home_id;
// If the asset is "borrowed_from_partner", allow the borrower (current user's funeral home) to manage it
if (($r->inventoryItem->status ?? null) === 'borrowed_from_partner') {
    $canManage = true;
}
            $statuses = [
                'reserved'   => 'Reserved',
                'in_use'     => 'In Use',
                'completed'  => 'Completed',
                'cancelled'  => 'Cancelled',
                'closed'     => 'Closed'
            ];
        @endphp
        @if($canManage)
            <form action="{{ route('funeral.assets.reservations.updateStatus', $r->id) }}" method="POST" class="d-flex align-items-center gap-1">
                @csrf @method('PATCH')
                <select name="status" class="form-select form-select-sm" style="width: 130px;">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ $r->status === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Update</button>
            </form>
        @else
            <span class="text-muted small">No actions</span>
        @endif
    @else
        {{-- RESOURCE SHARING (existing code) --}}
        {{-- REQUESTER Actions --}}
        @if($isRequester)
            @if($canCancel)
                <form action="{{ route('funeral.assets.reservations.cancel', $r->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this reservation?')">Cancel</button>
                </form>
            @endif
            @if($canReturnEarly)
                <form action="{{ route('funeral.assets.reservations.return', $r->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-warning" onclick="return confirm('Return asset now?')">Return Early</button>
                </form>
            @endif
            @if(!$canCancel && !$canReturnEarly)
                <span class="text-muted small">No actions</span>
            @endif
        @endif

        {{-- PROVIDER Actions --}}
        @if($isProvider)
            @if($canProviderCancel)
                <form action="{{ route('funeral.assets.reservations.cancel', $r->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this reservation?')">Cancel</button>
                </form>
            @endif
            @if($canMarkAsReceived)
                <form action="{{ route('funeral.assets.reservations.receive', $r->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-success" onclick="return confirm('Asset received and completed?')">Mark as Received</button>
                </form>
            @endif
            @if(!$canProviderCancel && !$canMarkAsReceived)
                <span class="text-muted small">No actions</span>
            @endif
        @endif

        @if(!$isRequester && !$isProvider)
            <span class="text-muted small">No actions</span>
        @endif
    @endif
</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-muted text-center">No reservations found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $reservations->links() }}
        </div>
    </div>
</x-layouts.funeral>
