<x-layouts.funeral>
    <div class="container py-4">
        <h2 class="mb-4 text-white">Resource Request Details</h2>
        <div class="card bg-dark text-white shadow rounded-4">
            <div class="card-body">
                <h5>Request #{{ $request->id }}</h5>
                <p><strong>Status:</strong> <span class="badge bg-primary">{{ ucfirst($request->status) }}</span></p>
                <p><strong>Requester:</strong> {{ $request->requester->name ?? '-' }}</p>
                <p><strong>Provider:</strong> {{ $request->provider->name ?? '-' }}</p>
                <p><strong>Item Needed:</strong> {{ $request->requestedItem->name ?? '-' }}</p>
                <p><strong>Provider's Item:</strong> {{ $request->providerItem->name ?? '-' }}</p>
                <p><strong>Quantity:</strong> {{ $request->quantity }}</p>
                <p><strong>Preferred Date:</strong> {{ $request->preferred_date }}</p>
                <p><strong>Delivery Method:</strong> {{ $request->delivery_method }}</p>
                <p><strong>Contact Name:</strong> {{ $request->contact_name }}</p>
                <p><strong>Contact Email:</strong> {{ $request->contact_email }}</p>
                <p><strong>Contact Mobile:</strong> {{ $request->contact_mobile }}</p>
                <p><strong>Notes:</strong> {{ $request->notes }}</p>
                <a href="{{ route('funeral.partnerships.resource_requests.index') }}" class="btn btn-secondary mt-3">Back to Requests</a>
            </div>
        </div>
    </div>
</x-layouts.funeral>
