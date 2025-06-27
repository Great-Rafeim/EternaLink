<x-client-layout>
    <div class="container py-5" style="max-width:650px;">
        <h2 class="fw-bold mb-4 text-primary-emphasis">Book a Cemetery Plot – {{ $user->name }}</h2>
        <div class="mb-3 text-secondary small">
            <b>Address:</b> {{ $cemetery->address ?? 'No address provided' }}<br>
            <b>Contact:</b> {{ $cemetery->contact_number ?? 'N/A' }}
        </div>

        <form method="POST" action="{{ route('client.cemeteries.booking.submit', $user->id) }}" enctype="multipart/form-data" id="cemetery-booking-form" autocomplete="on">
            @csrf

            <!-- Funeral Booking Selector -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Related Funeral Booking <span class="text-danger">*</span></label>
                <select name="booking_id" class="form-select form-select-lg @error('booking_id') is-invalid @enderror" required id="booking-selector">
                    <option value="">Select funeral booking...</option>
                    @foreach($bookings as $booking)
                        <option 
                            value="{{ $booking->id }}"
                            data-summary="{{ $booking->funeralHome->name ?? '' }} - {{ $booking->package->name ?? '' }}"
                        >
                            [#{{ $booking->id }}] {{ $booking->funeralHome->name ?? 'N/A' }} — {{ $booking->package->name ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
                <div class="small text-muted mt-1" id="booking-summary"></div>
                @error('booking_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Casket Size -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Casket Size <span class="text-danger">*</span></label>
                <input type="text" name="casket_size" value="{{ old('casket_size') }}" class="form-control form-control-lg @error('casket_size') is-invalid @enderror" required autocomplete="off">
                @error('casket_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <!-- Interment Date -->
            <div class="mb-4">
                <label class="form-label">Interment Date <span class="text-danger">*</span></label>
                <input 
                    type="date"
                    name="interment_date"
                    value="{{ old('interment_date') }}"
                    class="form-control @error('interment_date') is-invalid @enderror"
                    required
                    min="{{ now()->addDay()->toDateString() }}"
                    id="interment-date-field"
                >
                @error('interment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Plot Ownership -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Do you already have a plot purchased in this cemetery? <span class="text-danger">*</span></label>
                <div class="btn-group d-flex" role="group" aria-label="plot options">
                    <input type="radio" class="btn-check" name="has_plot" id="has-plot-yes" value="1" autocomplete="off" {{ old('has_plot')=='1' ? 'checked' : '' }} required>
                    <label class="btn btn-outline-success" for="has-plot-yes">Yes</label>
                    <input type="radio" class="btn-check" name="has_plot" id="has-plot-no" value="0" autocomplete="off" {{ old('has_plot')=='0' ? 'checked' : '' }} required>
                    <label class="btn btn-outline-danger" for="has-plot-no">No</label>
                </div>
                @error('has_plot') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div class="form-text mt-1">You must already own a plot in this cemetery to proceed.</div>
            </div>

            <!-- Proof of Purchase (file) -->
            <div class="mb-4" id="proof-section" style="display:none;">
                <label class="form-label fw-semibold">Upload Proof of Purchase <span class="text-danger">*</span></label>
                <input type="file" name="proof_of_purchase" class="form-control @error('proof_of_purchase') is-invalid @enderror" accept="image/*,application/pdf">
                @error('proof_of_purchase') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Accepted: JPG, PNG, PDF. Max size: 2MB.</div>
            </div>

            <!-- Required Documents -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Required Documents <span class="text-danger">*</span></label>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <input type="file" name="death_certificate" class="form-control @error('death_certificate') is-invalid @enderror" accept="image/*,application/pdf" required>
                        <label class="form-label small mt-1 mb-0">Death Certificate</label>
                        @error('death_certificate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <input type="file" name="burial_permit" class="form-control @error('burial_permit') is-invalid @enderror" accept="image/*,application/pdf" required>
                        <label class="form-label small mt-1 mb-0">Burial Permit</label>
                        @error('burial_permit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <input type="file" name="construction_permit" class="form-control @error('construction_permit') is-invalid @enderror" accept="image/*,application/pdf" required>
                        <label class="form-label small mt-1 mb-0">Construction Permit</label>
                        @error('construction_permit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button class="btn btn-primary rounded-pill px-4" type="submit">
                    <i class="bi bi-calendar-plus me-1"></i> Submit Booking
                </button>
                <a href="{{ route('client.cemeteries.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Bootstrap Modal for No Plot -->
    <div class="modal fade" id="noPlotModal" tabindex="-1" aria-labelledby="noPlotModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger shadow-lg">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="noPlotModalLabel"><i class="bi bi-exclamation-octagon"></i> Booking Not Allowed</h5>
          </div>
          <div class="modal-body fs-5">
            <p>You must already own a plot in this cemetery to book an interment.</p>
            <p>Please purchase a plot first from the cemetery administrator.</p>
          </div>
          <div class="modal-footer">
            <a href="{{ route('client.cemeteries.index') }}" class="btn btn-secondary">Back to Cemetery List</a>
          </div>
        </div>
      </div>
    </div>

    <script>
        // Booking summary display
        document.getElementById('booking-selector').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            document.getElementById('booking-summary').innerText = selected.dataset.summary || '';
        });

        // Autofill interment date when booking is selected
        const bookingDetails = @json($bookingDetails->mapWithKeys(function($detail, $bookingId) {
            return [
                $bookingId => [
                    'interment_cremation_date' => $detail->interment_cremation_date
                ]
            ];
        }));

        document.getElementById('booking-selector').addEventListener('change', function() {
            var bookingId = this.value;
            var dateField = document.getElementById('interment-date-field');
            if (bookingDetails[bookingId] && bookingDetails[bookingId].interment_cremation_date) {
                dateField.value = bookingDetails[bookingId].interment_cremation_date;
            } else {
                dateField.value = '';
            }
        });

        // Plot fields toggling
        function togglePlotFields() {
            let hasPlot = document.querySelector('input[name="has_plot"]:checked')?.value;
            let proofSection = document.getElementById('proof-section');
            if (hasPlot === '1') {
                proofSection.style.display = 'block';
            } else if (hasPlot === '0') {
                proofSection.style.display = 'none';
                // Show Bootstrap modal if "No"
                let modal = new bootstrap.Modal(document.getElementById('noPlotModal'));
                modal.show();
            } else {
                proofSection.style.display = 'none';
            }
        }
        document.querySelectorAll('input[name="has_plot"]').forEach(function(radio) {
            radio.addEventListener('change', togglePlotFields);
        });
        // Initialize on load
        togglePlotFields();
    </script>
</x-client-layout>
