<x-layouts.funeral>
    <div class="container py-5">
        <h2 class="fw-bold mb-4 text-primary">
            <i class="bi bi-search"></i> Review Client Information
        </h2>
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <h5 class="mb-2"><i class="bi bi-person-circle"></i> {{ $booking->client->name ?? 'N/A' }}</h5>
                <div><strong>Package:</strong> {{ $booking->package->name ?? 'N/A' }}</div>
                <div><strong>Status:</strong> <span class="badge bg-warning text-dark">{{ ucfirst($booking->status) }}</span></div>
                <div><strong>Details Submitted On:</strong> {{ $booking->updated_at->format('M d, Y h:i A') }}</div>
                <hr>
                {{-- You can show booking details here --}}
                <pre class="bg-light rounded p-3">{{ json_encode(json_decode($booking->details, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        <a href="{{ route('funeral.bookings.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Bookings
        </a>
    </div>
</x-layouts.funeral>
