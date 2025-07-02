@push('styles')
<style>

    .inventory-table {
        table-layout: fixed;
        width: 100%;
    }
    th.actions-col, td.actions-col {
        width: 180px;    /* Adjust as needed for your buttons */
        min-width: 150px;
        max-width: 200px;
    }
    .sort-caret {
        font-size: 1em;
        margin-left: 2px;
        vertical-align: middle;
    }
    .sort-caret.inactive {
        opacity: 0.3;
    }
</style>
@endpush


<div class="table-responsive">
    <table class="table table-dark table-hover table-bordered border-secondary align-middle text-white shadow-sm rounded-3">
<thead class="table-secondary text-dark">
<tr>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name','direction' => request('sort')==='name'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Item
            @if(request('sort')==='name')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'category','direction'=>request('sort')==='category'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Category
            @if(request('sort')==='category')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'brand','direction'=>request('sort')==='brand'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Brand
            @if(request('sort')==='brand')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'quantity','direction'=>request('sort')==='quantity'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Qty
            @if(request('sort')==='quantity')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'low_stock_threshold','direction'=>request('sort')==='low_stock_threshold'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Min. Qty
            @if(request('sort')==='low_stock_threshold')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'price','direction'=>request('sort')==='price'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Price
            @if(request('sort')==='price')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'selling_price','direction'=>request('sort')==='selling_price'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Selling Price
            @if(request('sort')==='selling_price')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'expiry_date','direction'=>request('sort')==='expiry_date'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Expiry Date
            @if(request('sort')==='expiry_date')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'status','direction'=>request('sort')==='status'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Status
            @if(request('sort')==='status')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th>
        <a href="{{ request()->fullUrlWithQuery(['sort'=>'shareable_quantity','direction'=>request('sort')==='shareable_quantity'&&request('direction')==='asc'?'desc':'asc']) }}"
           class="text-dark text-decoration-none">
            Shareable Qty
            @if(request('sort')==='shareable_quantity')
                <i class="bi bi-caret-{{ request('direction')==='asc'?'up':'down' }}-fill"></i>
            @endif
        </a>
    </th>
    <th class="text-center actions-col" style="width: 150px;">Actions</th>
</tr>
</thead>

        <tbody>
            @forelse ($items as $item)
                <tr class="{{ $item->quantity <= $item->low_stock_threshold ? 'table-danger' : '' }}">
                    <td>{{ $item->name }}</td>
                    <td>
                        <span>{{ $item->category->name ?? 'Uncategorized' }}</span>
                        @if($item->category && $item->category->is_asset)
                            <span class="badge bg-info text-dark ms-1">Bookable Asset</span>
                            @if($item->category->reservation_mode === 'continuous')
                                <span class="badge bg-primary ms-1">Continuous</span>
                            @elseif($item->category->reservation_mode === 'single_event')
                                <span class="badge bg-warning text-dark ms-1">Single Event</span>
                            @endif
                        @endif
                    </td>
                    <td>{{ $item->brand }}</td>
                    <td>
                        {{ $item->quantity }}
                        @if ($item->quantity <= $item->low_stock_threshold)
                            <span class="badge bg-warning text-dark ms-2">Low Stock</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $item->low_stock_threshold }}</span>
                    </td>
                    <td>
                        @if($item->price)
                            ₱{{ number_format($item->price, 2) }}
                        @else
                            <span class="text-secondary fw-semibold">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($item->selling_price)
                            ₱{{ number_format($item->selling_price, 2) }}
                        @else
                            <span class="text-secondary fw-semibold">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($item->expiry_date)
                            <span class="{{ \Carbon\Carbon::parse($item->expiry_date)->isPast() ? 'text-danger fw-bold' : '' }}">
                                {{ \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') }}
                            </span>
                        @else
                            <span class="text-secondary fw-semibold">N/A</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge
                            @if($item->status === 'available') bg-success
                            @elseif($item->status === 'in_use') bg-info
                            @elseif($item->status === 'reserved') bg-warning text-dark
                            @elseif($item->status === 'shared_to_partner') bg-primary
                            @elseif($item->status === 'borrowed_from_partner') bg-secondary
                            @elseif($item->status === 'maintenance') bg-danger
                            @else bg-secondary @endif
                        ">
                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                        </span>
                    </td>
                    <td>
                        @if($item->shareable)
                            <span class="badge bg-info text-dark">{{ $item->shareable_quantity ?? '-' }}</span>
                        @else
                            <span class="text-secondary fw-semibold">N/A</span>
                        @endif
                    </td>
                   <td class="text-center actions-col">
                        @if($item->status == 'borrowed_from_partner')
                            @php
                                // Try to find the related reservation for this borrowed item
                                $borrowedReservation = \App\Models\AssetReservation::where('borrowed_item_id', $item->id)
                                    ->whereIn('status', ['in_use', 'completed']) // Adjust if needed
                                    ->latest('reserved_start')
                                    ->first();
                            @endphp

                            @if($borrowedReservation)
                                <form action="{{ route('funeral.assets.reservations.return', $borrowedReservation->id) }}"
                                    method="POST"
                                    class="d-inline"
                                    style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-warning"
                                            onclick="return confirm('Return this borrowed item?')">
                                        <i class="bi bi-arrow-return-left"></i> Return
                                    </button>
                                </form>
                            @else
                                <span class="text-secondary fw-semibold">No reservation found</span>
                            @endif
                        @else
                            @php
                                $cannotEditOrDelete = in_array($item->status, ['shared_to_partner']);
                            @endphp

                            {{-- Edit Button --}}
                            @if($cannotEditOrDelete)
                                <button class="btn btn-sm btn-outline-secondary me-1" disabled
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Cannot edit while asset is borrowed by a partner.">
                                    Edit
                                </button>
                            @else
                                <a href="{{ route('funeral.items.edit', $item) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            @endif

                            {{-- Delete Button --}}
                            @if($cannotEditOrDelete)
                                <button class="btn btn-sm btn-outline-secondary" disabled
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Cannot delete while asset is borrowed by a partner.">
                                    Delete
                                </button>
                            @else
                                <form action="{{ route('funeral.items.destroy', $item) }}" method="POST" class="d-inline ajax-delete" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif

                            {{-- Request Button (unchanged) --}}
                            @if ($item->quantity <= $item->low_stock_threshold && !$cannotEditOrDelete)
                                <a href="{{ route('funeral.partnerships.resource_requests.request', $item->id) }}"
                                class="btn btn-sm btn-warning mt-1">
                                    Request
                                </a>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-secondary text-center fw-semibold">No inventory items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($items->hasPages())
        <div class="mt-3">
            {{ $items->links() }}
        </div>
    @endif
</div>

{{-- Initialize tooltips (if using Bootstrap 5 tooltips) --}}
@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
