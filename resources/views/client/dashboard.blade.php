<x-client-layout>
    <div class="container py-5">
        <h2 class="fw-bold mb-4" style="color:#1565c0;">Welcome, {{ auth()->user()->name }}!</h2>

        @if($bookings->isEmpty())
            <div class="row justify-content-center align-items-center min-vh-50">
                <div class="col-lg-7 col-md-9">
                    <div class="card shadow-lg border-0 rounded-4 p-4" style="background: #fff;">
                        <div class="card-body text-center">
                            <p class="lead mb-4 text-secondary">
                                Thank you for choosing EternaLink.<br>
                                You have no active bookings at the moment.
                            </p>
                            <a href="{{ route('client.parlors.index') }}" class="btn btn-lg btn-primary px-5 py-2 rounded-pill shadow-sm">
                                <i class="bi bi-search me-2"></i> Find Funeral Parlors
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg border-0 rounded-4 p-4 mb-4">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Your Bookings</h4>
                            <div class="table-responsive">
                                <table class="table align-middle table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Package</th>
                                            <th>Parlor</th>
                                            <th>Agent</th>
                                            <th>Status</th>
                                            <th>Requested On</th>
                                            <th>Main Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($bookings as $booking)
                                    @php
                                        $statuses = [
                                            'pending'      => ['label' => 'Pending',        'color' => 'warning',  'icon' => 'hourglass-split'],
                                            'assigned'     => ['label' => 'Agent Assigned', 'color' => 'info',     'icon' => 'person-badge'],
                                            'confirmed'    => ['label' => 'Confirmed',      'color' => 'success',  'icon' => 'check-circle'],
                                            'in_progress'  => ['label' => 'Filling Up',     'color' => 'secondary','icon' => 'pencil'],
                                            'for_initial_review' => ['label' => 'For Initial Review', 'color' => 'info', 'icon' => 'hourglass-top'],
                                            'for_review'   => ['label' => 'For Review',     'color' => 'warning',  'icon' => 'journal-check'],
                                            'approved'     => ['label' => 'Approved',       'color' => 'success',  'icon' => 'shield-check'],
                                            'ongoing'      => ['label' => 'Ongoing',        'color' => 'primary',  'icon' => 'arrow-repeat'],
                                            'completed'    => ['label' => 'Completed',      'color' => 'dark',     'icon' => 'check-circle'],
                                            'declined'     => ['label' => 'Declined',       'color' => 'danger',   'icon' => 'x-circle'],
                                            'canceled'     => ['label' => 'Canceled',       'color' => 'danger',   'icon' => 'slash-circle'],
                                        ];
                                        $status = $statuses[$booking->status] ?? ['label' => ucfirst($booking->status), 'color' => 'secondary', 'icon' => 'question-circle'];
                                    @endphp

                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $booking->package->name ?? 'N/A' }}</div>
                                                    <div class="text-muted small">
                                                        {{ $details['deceased_name'] ?? '' }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{ $booking->funeralHome->name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                @if($booking->agent)
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-person-badge"></i>
                                                        <span>{{ $booking->agent->name }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">Not assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $status['color'] }}-subtle text-{{ $status['color'] }} px-3 py-2">
                                                    <i class="bi bi-{{ $status['icon'] }}"></i> {{ $status['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $booking->created_at->format('M d, Y') }}
                                            </td>
                                            <td>
    {{-- PHASED MAIN ACTIONS --}}
    @if($booking->status === 'confirmed')
        <a href="{{ route('client.bookings.continue.edit', $booking->id) }}"
           class="btn btn-success btn-sm rounded-pill mb-1">
            <i class="bi bi-pencil-square"></i>
            Fill Out Booking Details
        </a>
        <div class="small text-warning mt-1">Please complete required info</div>
    @elseif($booking->status === 'in_progress')
        <a href="{{ route('client.bookings.continue.info', $booking->id) }}"
           class="btn btn-warning btn-sm rounded-pill mb-1">
            <i class="bi bi-pencil"></i>
            Continue Filling Out Forms
        </a>
        <div class="small text-warning mt-1">Continue your booking information</div>
    @elseif($booking->status === 'for_initial_review')
        <a href="{{ route('client.bookings.show', $booking->id) }}"
           class="btn btn-info btn-sm rounded-pill mb-1">
            <i class="bi bi-hourglass-top"></i>
            Waiting for Funeral Parlor to Set Fees
        </a>
        <div class="small text-muted mt-1">You can only view your booking while waiting.</div>
    @elseif($booking->status === 'for_review')

        <a href="{{ route('client.bookings.show', $booking->id) }}"
           class="btn btn-info btn-sm rounded-pill mb-1">
            <i class="bi bi-journal-check"></i>
            Waiting for Funeral Parlor Review        
        </a>
    @elseif($booking->status === 'approved')
        <span class="badge bg-success px-3 py-2">
            <i class="bi bi-shield-check"></i> Ready to Start
        </span>
    @elseif($booking->status === 'ongoing')
        <a href="{{ route('client.bookings.show', $booking->id) }}"
           class="btn btn-primary btn-sm rounded-pill mb-1">
            <i class="bi bi-arrow-repeat"></i>
            Service In Progress
        </a>
    @elseif($booking->status === 'completed')
        <a href="{{ route('client.bookings.show', $booking->id) }}"
           class="btn btn-dark btn-sm rounded-pill mb-1">
            <i class="bi bi-check-circle"></i>
            View Completed Booking
        </a>
    @else
        <a href="{{ route('client.bookings.show', $booking->id) }}"
           class="btn btn-outline-primary btn-sm rounded-pill mb-1">
            <i class="bi bi-eye"></i> View
        </a>
    @endif

    {{-- Cancel Booking Action --}}
    @if(in_array($booking->status, ['pending', 'confirmed', 'assigned', 'in_progress']))
        <form method="POST"
            action="{{ route('client.bookings.cancel', $booking->id) }}"
            onsubmit="return confirm('Are you sure you want to cancel this booking?');"
            class="mt-2">
            @csrf
            @method('PUT')
            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                <i class="bi bi-x-circle"></i> Cancel
            </button>
        </form>
    @endif
</td>

                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center">
                                {{ $bookings->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-client-layout>
