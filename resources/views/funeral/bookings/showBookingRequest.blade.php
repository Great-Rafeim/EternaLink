{{-- resources/views/funeral/bookings/showBookingRequest.blade.php --}}
<x-layouts.funeral>
    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-4">

                {{-- Heading --}}
                <h4 class="fw-bold mb-4 d-flex align-items-center" style="color: #2948ff;">
                    <i class="bi bi-clipboard2-check me-2"></i>
                    Booking Request #{{ $booking->id }}
                </h4>

                {{-- Status --}}
                <div class="mb-4">
                    <span class="fw-semibold text-secondary">Status:</span>
                    <span class="badge
                        @if($booking->status === 'pending') bg-warning text-dark
                        @elseif($booking->status === 'confirmed') bg-success
                        @elseif($booking->status === 'denied') bg-danger
                        @else bg-secondary
                        @endif
                        ms-2 px-3 py-2 fs-6 rounded-pill shadow-sm"
                    >
                        {{ ucfirst($booking->status) }}
                    </span>
                </div>

                {{-- Details --}}
                <div class="mb-4">
                    <div class="fw-semibold text-secondary mb-2">Details:</div>
                    @php $details = json_decode($booking->details, true); @endphp
                    <table class="table table-bordered table-striped rounded-3 overflow-hidden bg-light shadow-sm mb-0">
                        <tbody>
                        @foreach($details as $key => $value)
                            <tr>
                                <th style="width: 45%" class="bg-white text-secondary">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                <td class="bg-white">{{ $value }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Action Buttons --}}
                @if($booking->status === 'pending')
                <div class="d-flex flex-column flex-sm-row gap-3 mt-4">
                    <!-- Approve Button Trigger -->
                    <button type="button" class="btn btn-success rounded-pill px-4 flex-fill" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle me-1"></i> Approve
                    </button>
                    <!-- Deny Button Trigger -->
                    <button type="button" class="btn btn-danger rounded-pill px-4 flex-fill" data-bs-toggle="modal" data-bs-target="#denyModal">
                        <i class="bi bi-x-circle me-1"></i> Deny
                    </button>
                </div>
                @endif

            </div>
        </div>
    </div>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 modal-content-light">
            <div class="modal-header border-0">
                <h5 class="modal-title text-success" id="approveModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Confirm Approval
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fs-6 text-center text-dark">
                Are you sure you want to <span class="fw-semibold text-success">approve</span> this booking request?
            </div>
            <div class="modal-footer border-0 d-flex justify-content-center gap-2">
                <form action="{{ route('funeral.bookings.approve', $booking->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success rounded-pill px-4">Yes, Approve</button>
                </form>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Deny Modal --}}
<div class="modal fade" id="denyModal" tabindex="-1" aria-labelledby="denyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 modal-content-light">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger" id="denyModalLabel">
                    <i class="bi bi-x-circle me-2"></i>Confirm Denial
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body fs-6 text-center text-dark">
                Are you sure you want to <span class="fw-semibold text-danger">deny</span> this booking request?
            </div>
            <div class="modal-footer border-0 d-flex justify-content-center gap-2">
                <form action="{{ route('funeral.bookings.deny', $booking->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Yes, Deny</button>
                </form>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

</x-layouts.funeral>
