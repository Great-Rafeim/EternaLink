<x-layouts.funeral>
    <div class="container py-5">
        <h2 class="fw-bold mb-4">Customization Request for Booking #{{ $booking->id }}</h2>

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="mb-3">Client: {{ $booking->client->name }}</h4>
                <h5>Package: {{ $booking->package->name }}</h5>
                <hr>
                <h5>Customized Items:</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Substitute For</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customizedPackage->items as $item)
                            <tr>
                                <td>{{ $item->inventoryItem->name ?? '-' }}</td>
                                <td>
                                    @if($item->substitute_for && $item->substitute_for != $item->inventory_item_id)
                                        {{ optional($item->substituteFor)->name }}
                                    @else
                                        (Default)
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>₱{{ number_format($item->unit_price, 2) }}</td>
                                <td>₱{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3">
                    <strong>Total Price:</strong>
                    ₱{{ number_format($customizedPackage->custom_total_price, 2) }}
                </div>
                <hr>
<form method="POST" action="{{ route('funeral.bookings.customization.approve', [$booking->id, $customizedPackage->id]) }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-success">Approve</button>
</form>
<form method="POST" action="{{ route('funeral.bookings.customization.deny', [$booking->id, $customizedPackage->id]) }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-danger">Deny</button>
</form>

                <a href="{{ route('funeral.bookings.index') }}" class="btn btn-outline-secondary ms-2">Back to Bookings</a>
            </div>
        </div>
    </div>
</x-layouts.funeral>
