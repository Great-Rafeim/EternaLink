<div class="table-responsive">
    <table class="table table-dark table-hover table-bordered border-secondary align-middle text-white shadow-sm rounded-3">
        <thead class="table-secondary text-dark">
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Quantity</th>
                <th>Low Stock Threshold</th>
                <th>Price</th>
                <th>Selling Price</th>
                <th>Expiry Date</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr class="{{ $item->quantity <= $item->low_stock_threshold ? 'table-danger' : '' }}">
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category->name ?? 'Uncategorized' }}</td>
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
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item->selling_price)
                            ₱{{ number_format($item->selling_price, 2) }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($item->expiry_date)
                            <span class="{{ \Carbon\Carbon::parse($item->expiry_date)->isPast() ? 'text-danger fw-bold' : '' }}">
                                {{ \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') }}
                            </span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge
                            @if($item->status === 'available') bg-success
                            @elseif($item->status === 'in_use') bg-info
                            @else bg-secondary @endif
                        ">
                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('funeral.items.edit', $item) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                        <form action="{{ route('funeral.items.destroy', $item) }}" method="POST" class="d-inline ajax-delete" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-muted text-center">No inventory items found.</td>
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
