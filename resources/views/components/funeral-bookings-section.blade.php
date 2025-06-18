<div class="card shadow-sm mb-5 border-0">
    <div class="card-header bg-{{ $color }} bg-opacity-10">
        <h4 class="mb-0 fw-bold text-{{ $color }}">
            <i class="bi {{ $icon }}"></i> {{ $title }}
        </h4>
        <div class="small text-muted">{{ $subtitle }}</div>
    </div>
    <div class="card-body">
        @if($bookings->isEmpty())
            <p class="text-muted m-0">No bookings found.</p>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Package</th>
                        @if(isset($statusBadge))<th>Status</th>@endif
                        <th>Agent</th>
                        <th>{{ $dateLabel }}</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $booking)
                    @php
                        $details = json_decode($booking->details, true) ?? [];
                        $clientName = $booking->client->name ?? 'N/A';
                        $packageName = $booking->package->name ?? 'N/A';
                        $agentName = $booking->agent->name ?? 'Unassigned';
                        $dateValue = $booking->created_at ?? now();
                        if(isset($booking->customizedPackage) && $dateLabel === 'Requested On') {
                            $dateValue = $booking->customizedPackage->updated_at ?? $booking->updated_at;
                        }
                        $status = ucfirst($booking->status ?? 'N/A');
                        $badge = $statusBadge ?? ['secondary', 'question-circle'];
                    @endphp
                    <tr>
                        <td>
                            <i class="bi bi-person-circle me-1"></i> {{ $clientName }}
                        </td>
                        <td>
                            <i class="bi bi-box2-heart me-1"></i> {{ $packageName }}
                        </td>
                        @if(isset($statusBadge))
                        <td>
                            <span class="badge bg-{{ $badge[0] }} text-dark d-flex align-items-center gap-1 px-2">
                                <i class="bi bi-{{ $badge[1] }}"></i> {{ $status }}
                            </span>
                        </td>
                        @endif
                        <td>
                            <i class="bi bi-people me-1"></i> {{ $agentName }}
                        </td>
                        <td>
                            <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($dateValue)->format('M d, Y') }}
                        </td>
                        <td>
                            {{-- Dynamic Actions --}}
                            @if(in_array('approve', $actions))
                                <form method="POST" action="{{ route('funeral.bookings.approve', $booking->id) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill mb-1">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </form>
                            @endif
                            @if(in_array('deny', $actions))
                                <form method="POST" action="{{ route('funeral.bookings.deny', $booking->id) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill mb-1">
                                        <i class="bi bi-x-circle"></i> Deny
                                    </button>
                                </form>
                            @endif
                            @if(in_array('review', $actions))
                                <a href="{{ route('funeral.bookings.review.details', $booking->id) }}" class="btn btn-warning btn-sm rounded-pill mb-1">
                                    <i class="bi bi-search"></i> Review Info
                                </a>
                            @endif
                            @if(in_array('customization', $actions))
                                <a href="{{ route('funeral.bookings.customization.show', $booking->id) }}" class="btn btn-danger btn-sm rounded-pill mb-1">
                                    <i class="bi bi-pencil-square"></i> Review Customization
                                </a>
                            @endif
                            @if(in_array('view', $actions))
                                <a href="{{ route('funeral.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm mb-1">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            @endif
                            @if($modal ?? false)
                                <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bookingModal{{$booking->id}}">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                {{-- You can drop your modal markup here or outside the loop --}}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
