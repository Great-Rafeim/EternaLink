<x-cemetery-layout>
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <h2 class="fw-bold text-white mb-0">
                Cemetery Bookings
            </h2>
            <form method="get" class="d-flex align-items-center gap-2">
                <select name="status"
                        class="form-select form-select-sm bg-dark text-white border-secondary"
                        style="width:auto;"
                        onchange="this.form.submit()">
                    <option value="" {{ $status=='' ? 'selected' : '' }}>All</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @if($status==$s) selected @endif>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card bg-dark border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-white">Client</th>
                                <th class="text-white">Casket Size</th>
                                <th class="text-white">Interment Date</th>
                                <th class="text-white">Status</th>
                                <th class="text-white" style="width:90px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                <tr>
                                    <td class="text-white">{{ $booking->user->name }}</td>
                                    <td class="text-white">{{ $booking->casket_size }}</td>
                                    <td class="text-white">
                                        {{ $booking->interment_date ? \Carbon\Carbon::parse($booking->interment_date)->format('M d, Y') : '-' }}
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($booking->status) {
                                                'pending' => 'bg-warning text-dark',
                                                'approved' => 'bg-success text-white',
                                                'rejected' => 'bg-danger text-white',
                                                'cancelled' => 'bg-secondary text-white',
                                                default => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($booking->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('cemetery.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-light">
                                        No bookings found for this status.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 px-3">
                    {{ $bookings->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</x-cemetery-layout>
