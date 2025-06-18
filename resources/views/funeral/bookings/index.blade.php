{{-- resources/views/funeral/bookings/index.blade.php --}}

<x-layouts.funeral>
    <div class="container py-5">
        <h2 class="fw-bold mb-4" style="color: #1565c0;">
            <i class="bi bi-list-task me-2"></i>
            Booking Management
        </h2>

        {{-- 1. New Bookings --}}
        @if(isset($newBookings) && $newBookings->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-primary bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-bookmark-plus"></i> New Bookings
                    </h4>
                    <div class="small text-muted">Bookings needing review or action.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($newBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-warning d-flex align-items-center gap-1 px-2">
                                                <i class="bi bi-hourglass-split"></i> {{ ucfirst($booking->status) }}
                                            </span>
                                        </td>
                                        <td><i class="bi bi-people me-1"></i> {{ $booking->agent->name ?? 'Unassigned' }}</td>
                                        <td><i class="bi bi-calendar-event me-1"></i> {{ $booking->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. Client is Filling Out Forms --}}
        @if(isset($inProgressBookings) && $inProgressBookings->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-secondary bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-secondary">
                        <i class="bi bi-pencil"></i> Client is Filling Out Forms
                    </h4>
                    <div class="small text-muted">Client is currently filling out required booking information.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Started On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inProgressBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-secondary text-dark d-flex align-items-center gap-1 px-2">
                                                <i class="bi bi-pencil"></i> Filling Up
                                            </span>
                                        </td>
                                        <td><i class="bi bi-people me-1"></i> {{ $booking->agent->name ?? 'Unassigned' }}</td>
                                        <td><i class="bi bi-calendar-event me-1"></i> {{ $booking->updated_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        {{-- 2.5 For Initial Review --}}
        @if(isset($forInitialReviewBookings) && $forInitialReviewBookings->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-info bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-info">
                        <i class="bi bi-hourglass-top"></i> For Initial Review (Set Other Fees)
                    </h4>
                    <div class="small text-muted">
                        Bookings waiting for you to set other fees before client proceeds.
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Submitted On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($forInitialReviewBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info text-dark d-flex align-items-center gap-1 px-2">
                                                <i class="bi bi-hourglass-top"></i> For Initial Review
                                            </span>
                                        </td>
                                        <td>
                                            <i class="bi bi-people me-1"></i>
                                            {{ $booking->agent->name ?? 'Unassigned' }}
                                        </td>
                                        <td>
                                            <i class="bi bi-calendar-event me-1"></i>
                                            {{ $booking->updated_at->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> Set Fees
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        {{-- 3. Client Information Submitted --}}
        @if(isset($readyForReviewBookings) && $readyForReviewBookings->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-warning bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-warning">
                        <i class="bi bi-journal-check"></i> Client Information Submitted
                    </h4>
                    <div class="small text-muted">Clients have submitted their booking details. Please review and confirm.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Submitted On</th>
                                    <th>Agent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($readyForReviewBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-calendar-check me-1"></i> {{ $booking->updated_at->format('M d, Y') }}</td>
                                        <td><i class="bi bi-people me-1"></i> {{ $booking->agent->name ?? 'Unassigned' }}</td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 4. Approved (Ready to Start) --}}
        @if(isset($approvedBookings) && $approvedBookings->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-success bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-success">
                        <i class="bi bi-shield-check"></i> Approved (Ready to Start)
                    </h4>
                    <div class="small text-muted">These bookings are ready to be started.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Approved On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($approvedBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-success d-flex align-items-center gap-1 px-2">
                                                <i class="bi bi-shield-check"></i> Approved
                                            </span>
                                        </td>
                                        <td><i class="bi bi-people me-1"></i> {{ $booking->agent->name ?? 'Unassigned' }}</td>
                                        <td><i class="bi bi-calendar-check me-1"></i> {{ $booking->updated_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 5. Ongoing Service --}}
        @if(isset($ongoingBookings) && $ongoingBookings->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-info bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-info">
                        <i class="bi bi-arrow-repeat"></i> Ongoing Service
                    </h4>
                    <div class="small text-muted">Active bookings currently in progress.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Started On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ongoingBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info text-dark d-flex align-items-center gap-1 px-2">
                                                <i class="bi bi-arrow-repeat"></i> Ongoing
                                            </span>
                                        </td>
                                        <td><i class="bi bi-people me-1"></i> {{ $booking->agent->name ?? 'Unassigned' }}</td>
                                        <td><i class="bi bi-calendar-check me-1"></i> {{ $booking->updated_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 6. Completed Bookings --}}
        @if(isset($doneBookings) && $doneBookings->count())
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-check-circle"></i> Completed Bookings
                    </h4>
                    <div class="small text-muted">Recently completed bookings.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Completed On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doneBookings as $booking)
                                    <tr>
                                        <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                        <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-dark text-light d-flex align-items-center gap-1 px-2">
                                                <i class="bi bi-check-circle"></i> Completed
                                            </span>
                                        </td>
                                        <td><i class="bi bi-people me-1"></i> {{ $booking->agent->name ?? 'Unassigned' }}</td>
                                        <td><i class="bi bi-calendar-check me-1"></i> {{ $booking->updated_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- 7. Customization Requests --}}
        @if(isset($customizationRequests) && $customizationRequests->count())
            <div class="card shadow-sm mb-5 border-0">
                <div class="card-header bg-danger bg-opacity-10">
                    <h4 class="mb-0 fw-bold text-danger">
                        <i class="bi bi-sliders"></i> Customization Requests
                    </h4>
                    <div class="small text-muted">Awaiting your review and action.</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Requested On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customizationRequests as $booking)
                                    @php
                                        $pendingCustomization = $booking->customizationRequests->first();
                                    @endphp
                                    @if($pendingCustomization)
                                        <tr>
                                            <td><i class="bi bi-person-circle me-1"></i> {{ $booking->client->name ?? 'N/A' }}</td>
                                            <td><i class="bi bi-box2-heart me-1"></i> {{ $booking->package->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-warning text-dark d-flex align-items-center gap-1 px-2">
                                                    <i class="bi bi-sliders"></i>
                                                    {{ ucfirst($pendingCustomization->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <i class="bi bi-calendar-event me-1"></i>
                                                {{ $pendingCustomization->updated_at->format('M d, Y') }}
                                            </td>
                                            <td>
                                                <a href="{{ route('funeral.bookings.customization.show', [$booking->id, $pendingCustomization->id]) }}"
                                                    class="btn btn-danger btn-sm rounded-pill">
                                                    <i class="bi bi-pencil-square"></i> Review Customization
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.funeral>
