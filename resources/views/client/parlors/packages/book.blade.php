<x-client-layout>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow rounded-4 border-0 p-4">
                    <h2 class="fw-bold mb-3" style="color: #1565c0;">
                        Book Service Package
                    </h2>
                    <div class="mb-4">
                        <h4 class="mb-2 text-primary">{{ $package->name }}</h4>
                        <div>
                            <span class="badge bg-primary-subtle text-primary" style="font-size:1.1rem;">
                                ₱{{ number_format($package->total_price, 2) }}
                            </span>
                        </div>
                        <div class="mt-2 text-secondary">
                            {{ $package->description ?? 'No description provided.' }}
                        </div>
                    </div>

                    {{-- Inside your existing card, replace only the <form> ... </form> part with this: --}}

                    <form id="bookingForm" method="POST" action="{{ route('client.parlors.packages.book.submit', $package->id) }}">
                        @csrf

                        {{-- Hidden fields: these are used for the booking reference --}}
                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                        <input type="hidden" name="funeral_home_id" value="{{ $package->funeral_home_id }}">

                        {{-- Deceased Details --}}
                        <div class="mb-3">
                            <label class="form-label">Name of Deceased <span class="text-danger">*</span></label>
                            <input type="text" name="deceased_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Death</label>
                            <input type="date" name="date_of_death" class="form-control">
                        </div>
                        
                        {{-- Client Details --}}
                        <div class="mb-3">
                            <label class="form-label">Your Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" class="form-control" value="{{ old('client_name', auth()->user()->name ?? '') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                        
                        {{-- Schedule --}}
                        <div class="mb-3">
                            <label class="form-label">
                                Preferred Date to Start Funeral Services <span class="text-danger">*</span>
                                <br>
                                <small class="text-muted">(e.g., when you want the wake or initial arrangements to begin)</small>
                            </label>
                            <input type="date" name="preferred_schedule" class="form-control" required>
                        </div>

                        {{-- Additional Notes --}}
                        <div class="mb-3">
                            <label class="form-label">Additional Notes / Requests</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
    <button type="button" class="btn btn-success rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#confirmBookingModal">
        <i class="bi bi-calendar-check"></i> Confirm Booking
    </button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 ms-2">
                            Cancel
                        </a>
                    </form>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmBookingModal" tabindex="-1" aria-labelledby="confirmBookingLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmBookingLabel">Booking Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Your booking request will be sent to the funeral parlor. Please wait for confirmation before making any arrangements. You will be notified once your booking is approved or if further information is needed.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-3" id="finalConfirmBtn">Proceed</button>
            </div>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>

 <script>
document.getElementById('finalConfirmBtn').addEventListener('click', function() {
    document.getElementById('bookingForm').submit();
});
</script>
   
</x-client-layout>