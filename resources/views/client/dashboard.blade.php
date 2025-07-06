<x-client-layout>

<style>
    /* This ensures all action columns have a minimum width for button stacking */
    td[style*="min-width: 220px;"] {
        vertical-align: middle;
    }
    .table .btn {
        min-width: 180px;
    }
    .table .btn + .btn, .table form + form, .table .btn + form {
        margin-top: 0.25rem;
    }
</style>

    <div class="container py-5">
        <h2 class="fw-bold mb-4" style="color:#1565c0;">Welcome, {{ auth()->user()->name }}!</h2>

                <ul class="nav nav-tabs custom-nav-tabs" id="bookingsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="funeral-tab" data-bs-toggle="tab" data-bs-target="#funeral" type="button" role="tab" aria-controls="funeral" aria-selected="true">
                            <i class="bi bi-house-heart me-1"></i> Funeral Bookings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cemetery-tab" data-bs-toggle="tab" data-bs-target="#cemetery" type="button" role="tab" aria-controls="cemetery" aria-selected="false">
                            <i class="bi bi-tree me-1"></i> Cemetery Bookings
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="bookingsTabContent">

                    {{-- Funeral Bookings Tab --}}
                    <div class="tab-pane fade show active" id="funeral" role="tabpanel" aria-labelledby="funeral-tab">
                        @if($bookings->isEmpty())
                            <div class="card card-no-top-shadow border-0 rounded-bottom-3 rounded-top-0 p-4" style="background: #fff;">
                                <div class="card-body text-center">
                                    <p class="lead mb-4 text-secondary">
                                        Thank you for choosing EternaLink.<br>
                                        You have no active bookings at the moment.
                                    </p>
                                    <a href="{{ route('client.parlors.index') }}" class="btn btn-lg btn-primary px-5 py-2 rounded-pill shadow-sm">
                                        <i class="bi bi-search me-2"></i> Funeral Service Providers
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="card card-no-top-shadow border-0 rounded-bottom-3 rounded-top-0 p-0 mb-4">
                                <div class="card-body px-0 py-4">
                                    <h4 class="fw-bold mb-3 px-4">Your Bookings</h4>
                                    <div class="table-responsive px-4">
                                        <table class="table align-middle table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Package</th>
                                                    <th>Parlor</th>
                                                    <th>Agent</th>
                                                    <th>Status</th>
                                                    <th>Requested On</th>
                                                    <th>Actions</th>
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
                                                    'cancelled'     => ['label' => 'cancelled',       'color' => 'danger',   'icon' => 'slash-circle'],
                                                    'pending_payment'   => ['label' => 'Payment Pending','color' => 'primary',  'icon' => 'credit-card'],
                                                    'paid'              => ['label' => 'Paid',           'color' => 'success',  'icon' => 'check2-circle'],

                                                    ];
                                                $status = $statuses[$booking->status] ?? ['label' => ucfirst($booking->status), 'color' => 'secondary', 'icon' => 'question-circle'];
                                                $assignedAgent = $booking->bookingAgent->agentUser ?? null;
                                            @endphp

                                                <tr>
                                                    <td>
                                                <div>
                                                    <div class="fw-bold">{{ $booking->package->name ?? 'N/A' }}</div>
                                                    <div class="text-muted small">
                                                        {{-- Add deceased name if available --}}
                                                        {{ $booking->detail->deceased_first_name ?? '' }}
                                                        {{ $booking->detail->deceased_last_name ?? '' }}
                                                    </div>
                                                </div>
                                                    </td>
                                                    <td>
                                                        {{ $booking->funeralHome->name ?? 'N/A' }}
                                                    </td>
                                                    <td>
                                                        @if($assignedAgent)
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi bi-person-badge"></i>
                                                                <span>{{ $assignedAgent->name }}</span>
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
    <div class="d-flex flex-column align-items-start gap-2">
        @if($booking->status === 'confirmed')
            <a href="{{ route('client.bookings.continue.edit', $booking->id) }}"
                class="btn btn-success btn-sm rounded-pill"
                title="Fill out booking info">
                <i class="bi bi-pencil-square"></i> Fill
            </a>
        @elseif($booking->status === 'in_progress')

            <a href="{{ route('client.bookings.continue.info', $booking->id) }}"
                class="btn btn-warning btn-sm rounded-pill"
                title="Continue filling personal details">
                <i class="bi bi-pencil"></i> Continue
            </a>
        @elseif($booking->status === 'for_initial_review')
            <a href="{{ route('client.bookings.continue.edit', $booking->id) }}"
                class="btn btn-outline-secondary btn-sm rounded-pill"
                title="Edit booking details">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a href="{{ route('client.bookings.show', $booking->id) }}"
                class="btn btn-info btn-sm rounded-pill"
                title="Awaiting parlor review">
                <i class="bi bi-hourglass-top"></i> Awaiting
            </a>
        @elseif($booking->status === 'for_review')
        <a href="{{ route('client.bookings.continue.info', $booking->id) }}"
                class="btn btn-outline-secondary btn-sm rounded-pill"
                title="Edit booking details">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a href="{{ route('client.bookings.show', $booking->id) }}"
                class="btn btn-info btn-sm rounded-pill"
                title="Waiting for parlor review">
                <i class="bi bi-journal-check"></i> Awaiting
            </a>
        @elseif($booking->status === 'approved')
            <span class="btn btn-success btn-sm rounded-pill disabled"
                title="Booking approved">
                <i class="bi bi-shield-check"></i> Ready
            </span>
        @elseif($booking->status === 'pending_payment')
            <a href="{{ route('client.bookings.payment', $booking->id) }}"
                class="btn btn-warning btn-sm rounded-pill"
                title="Proceed to payment">
                <i class="bi bi-cash-coin"></i> Pay Now
            </a>
        @elseif($booking->status === 'ongoing')
            <a href="{{ route('client.bookings.show', $booking->id) }}"
                class="btn btn-primary btn-sm rounded-pill"
                title="Service ongoing">
                <i class="bi bi-arrow-repeat"></i> Ongoing
            </a>
        @elseif($booking->status === 'completed')
            <a href="{{ route('client.bookings.show', $booking->id) }}"
                class="btn btn-dark btn-sm rounded-pill"
                title="See completed booking">
                <i class="bi bi-check-circle"></i> View
            </a>
        @else
            <a href="{{ route('client.bookings.show', $booking->id) }}"
                class="btn btn-outline-primary btn-sm rounded-pill"
                title="View booking">
                <i class="bi bi-eye"></i> View
            </a>
        @endif

        @if(in_array($booking->status, ['pending', 'confirmed', 'assigned', 'in_progress']))
            <form method="POST"
                action="{{ route('client.bookings.cancel', $booking->id) }}"
                onsubmit="return confirm('Are you sure you want to cancel this booking?');"
                style="margin-bottom: 0;">
                @csrf
                @method('PUT')
                <button type="submit"
                    class="btn btn-outline-danger btn-sm rounded-pill"
                    title="Cancel booking">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            </form>
        @endif
    </div>

    {{-- Status Helper/Warning --}}
    <div class="small mt-2" style="min-height: 18px;">
        @if($booking->status === 'confirmed' || $booking->status === 'in_progress')
            <span class="text-warning">Info required</span>
        @elseif($booking->status === 'for_initial_review')
            <span class="text-muted">Editable under review</span>
        @elseif($booking->status === 'for_review')
            <span class="text-muted">View only</span>
        @elseif($booking->status === 'in_progress')
            <span class="text-warning">Update before review</span>
        @endif
    </div>
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
                        @endif
                    </div>

                    {{-- Cemetery Bookings Tab --}}
<div class="tab-pane fade" id="cemetery" role="tabpanel" aria-labelledby="cemetery-tab">
    <div class="card card-no-top-shadow border-0 rounded-bottom-3 rounded-top-0 p-0 mb-4">
        <div class="card-body px-0 py-4">
            <h4 class="fw-bold mb-3 px-4">Your Cemetery Bookings</h4>
            <div class="table-responsive px-4">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Cemetery</th>
                            <th>Casket Size</th>
                            <th>Interment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($cemeteryBookings as $cemeteryBooking)
                        @php
                            $statusColor = $cemeteryBooking->status === 'approved' ? 'success' : ($cemeteryBooking->status === 'rejected' ? 'danger' : 'warning');
                            $statusIcon = $cemeteryBooking->status === 'approved' ? 'shield-check' : ($cemeteryBooking->status === 'rejected' ? 'x-circle' : 'hourglass');
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold">
                                    {{ $cemeteryBooking->cemetery->user->name ?? $cemeteryBooking->cemetery->name ?? 'N/A' }}
                                </div>
                                <div class="text-muted small">{{ $cemeteryBooking->cemetery->address ?? '' }}</div>
                            </td>
                            <td>{{ $cemeteryBooking->casket_size }}</td>
                            <td>
                                {{ $cemeteryBooking->interment_date ? \Carbon\Carbon::parse($cemeteryBooking->interment_date)->format('M d, Y') : '-' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} px-3 py-2">
                                    <i class="bi bi-{{ $statusIcon }}"></i> {{ ucfirst($cemeteryBooking->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('client.cemeteries.show', $cemeteryBooking->id) }}"
                                    class="btn btn-outline-primary btn-sm rounded-pill mb-1">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @if($cemeteryBooking->status === 'pending')
                                    <form method="POST" action="{{ route('client.cemeteries.cancel', $cemeteryBooking->id) }}"
                                          class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this cemetery booking?');">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill mb-1">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                <span>No cemetery bookings found.</span>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $cemeteryBookings->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
                </div>
            </div>

    <style>
        .custom-nav-tabs {
            border-bottom: 0;
            border-radius: 1rem 1rem 0 0 !important;
            overflow: hidden;
            /* Ensure the tab border sits above card's border */
            margin-bottom: 0;
        }
        .custom-nav-tabs .nav-link {
            border-radius: 1rem 1rem 0 0 !important;
            margin-bottom: -1px;
        }
        /* Remove card top radius to connect flush with tabs */
        .card {
            border-radius: 0 0 1rem 1rem !important;
            max-width: 100%;
        }
        /* Remove shadow from card top, keep sides and bottom */
        .card-no-top-shadow {
            box-shadow: 0 4px 24px -4px rgba(60,60,140,0.08), 0 1.5rem 3rem rgba(60,60,140,0.07) !important;
        }
        @media (min-width: 992px) {
            .card {
                min-width: 950px;
            }
        }
        /* Table row hover */
        .table-hover tbody tr:hover {
            background-color: #f2f4f8;
            transition: background 0.15s;
        }
        /* Fix card and nav alignment */
        .col-lg-11, .col-12 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .card-body {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        /* Add side padding only for content */
        .px-4 {
            padding-left: 2rem !important;
            padding-right: 2rem !important;
        }
        /* Sticky footer fix for flex layout */
        body, html {
            height: 100%;
        }
        x-client-layout > .container {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
    </style>            
</x-client-layout>
