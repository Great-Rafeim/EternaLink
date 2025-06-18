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
                    <option value="all">All</option>
                    <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                    <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Category</label>
                <select name="category" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Asset</label>
                <select name="asset" class="form-select">
                    <option value="">All</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ request('asset') == $asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>
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
                        <th>Category</th>
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
                    <tr>
                        <td>{{ $r->inventoryItem->name ?? 'N/A' }}</td>
                        <td>{{ $r->inventoryItem->category->name ?? '-' }}</td>
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
                        <td>{{ $r->reserved_start->format('Y-m-d H:i') }}</td>
                        <td>{{ $r->reserved_end->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge 
                                {{ 
                                    $r->status == 'reserved' ? 'bg-warning' : 
                                    ($r->status == 'in_use' ? 'bg-info' :
                                    ($r->status == 'completed' ? 'bg-success' : 'bg-secondary')) 
                                }}">
                                {{ ucfirst(str_replace('_', ' ', $r->status)) }}
                            </span>
                        </td>
                        <td>{{ $r->creator->name ?? 'N/A' }}</td>
                        <td>
                            <form action="{{ route('funeral.assets.reservations.updateStatus', $r->id) }}" method="POST" class="d-flex gap-2 align-items-center">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-select form-select-sm me-2">
                                    <option value="reserved" {{ $r->status == 'reserved' ? 'selected' : '' }}>Reserved</option>
                                    <option value="in_use" {{ $r->status == 'in_use' ? 'selected' : '' }}>In Use</option>
                                    <option value="completed" {{ $r->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="available" {{ $r->status == 'available' ? 'selected' : '' }}>Available</option>
                                    <option value="cancelled" {{ $r->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted text-center">No reservations found.</td>
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
