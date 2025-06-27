{{-- resources/views/client/cemeteries/show.blade.php --}}
<x-client-layout>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">

                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">

                        <div class="mb-4 d-flex align-items-center justify-content-between">
                            <h4 class="fw-bold mb-0">
                                <i class="bi bi-tree me-2"></i>
                                Cemetery Booking Details
                            </h4>
                            <a href="{{ route('client.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>

                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Cemetery</dt>
                            <dd class="col-sm-8">
                                {{ $cemeteryBooking->cemetery->user->name ?? 'N/A' }}
                                <div class="small text-muted">{{ $cemeteryBooking->cemetery->address ?? '' }}</div>
                            </dd>

                            <dt class="col-sm-4 text-muted">Client</dt>
                            <dd class="col-sm-8">
                                {{ $cemeteryBooking->user->name ?? 'N/A' }}
                                <div class="small text-muted">{{ $cemeteryBooking->user->email ?? '' }}</div>
                            </dd>

                            <dt class="col-sm-4 text-muted">Related Funeral Booking</dt>
                            <dd class="col-sm-8">
                                @if($cemeteryBooking->funeralBooking)
                                    <span class="fw-semibold">#{{ $cemeteryBooking->funeralBooking->id }}</span>
                                    <div class="small text-muted">
                                        {{ $cemeteryBooking->funeralBooking->funeralHome->name ?? '' }}
                                        — {{ $cemeteryBooking->funeralBooking->package->name ?? '' }}
                                    </div>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Deceased Name</dt>
                            <dd class="col-sm-8">{{ $details['deceased_name'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-4 text-muted">Plot Assigned</dt>
                            <dd class="col-sm-8">
                                @if($cemeteryBooking->plot_id && $cemeteryBooking->plot)
                                    <span class="fw-bold text-success">Plot #{{ $cemeteryBooking->plot->plot_number }}</span>
                                    <span class="text-muted small">
                                        ({{ ucfirst($cemeteryBooking->plot->type) }}, Section: {{ $cemeteryBooking->plot->section ?? 'N/A' }}, Block: {{ $cemeteryBooking->plot->block ?? 'N/A' }})
                                    </span>
                                @else
                                    <span class="text-warning">Waiting for cemetery to assign</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Casket Size</dt>
                            <dd class="col-sm-8">{{ $cemeteryBooking->casket_size }}</dd>

                            <dt class="col-sm-4 text-muted">Interment Date</dt>
                            <dd class="col-sm-8">
                                {{ $cemeteryBooking->interment_date ? \Carbon\Carbon::parse($cemeteryBooking->interment_date)->format('M d, Y') : '-' }}
                            </dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $cemeteryBooking->status == 'approved' ? 'success' : ($cemeteryBooking->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($cemeteryBooking->status) }}
                                </span>
                            </dd>

                            @if($cemeteryBooking->admin_notes)
                                <dt class="col-sm-4 text-muted">Admin Notes</dt>
                                <dd class="col-sm-8">
                                    <div class="alert alert-info py-2 px-3 mb-0">
                                        <i class="bi bi-info-circle"></i>
                                        {{ $cemeteryBooking->admin_notes }}
                                    </div>
                                </dd>
                            @endif

                        </dl>

                        <hr class="my-4">

                        <h5 class="fw-semibold mb-3"><i class="bi bi-file-earmark-text me-1"></i> Uploaded Documents</h5>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="small mb-1">Death Certificate</label>
                                @if($cemeteryBooking->death_certificate_path)
                                    <button class="btn btn-outline-primary btn-sm w-100 doc-view-btn" data-url="{{ $cemeteryBooking->deathCertificateUrl }}">
                                        <i class="bi bi-file-earmark-pdf"></i> View
                                    </button>
                                @else
                                    <span class="text-muted small">No file</span>
                                @endif
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="small mb-1">Burial Permit</label>
                                @if($cemeteryBooking->burial_permit_path)
                                    <button class="btn btn-outline-primary btn-sm w-100 doc-view-btn" data-url="{{ $cemeteryBooking->burialPermitUrl }}">
                                        <i class="bi bi-file-earmark-pdf"></i> View
                                    </button>
                                @else
                                    <span class="text-muted small">No file</span>
                                @endif
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="small mb-1">Construction Permit</label>
                                @if($cemeteryBooking->construction_permit_path)
                                    <button class="btn btn-outline-primary btn-sm w-100 doc-view-btn" data-url="{{ $cemeteryBooking->constructionPermitUrl }}">
                                        <i class="bi bi-file-earmark-pdf"></i> View
                                    </button>
                                @else
                                    <span class="text-muted small">No file</span>
                                @endif
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="small mb-1">Proof of Purchase</label>
                                @if($cemeteryBooking->proof_of_purchase_path)
                                    <button class="btn btn-outline-primary btn-sm w-100 doc-view-btn" data-url="{{ $cemeteryBooking->proofOfPurchaseUrl }}">
                                        <i class="bi bi-file-earmark-pdf"></i> View
                                    </button>
                                @else
                                    <span class="text-muted small">No file</span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JS: Open documents in new tab --}}
    <script>
        document.querySelectorAll('.doc-view-btn').forEach(function(btn){
            btn.addEventListener('click', function(e){
                e.preventDefault();
                window.open(this.dataset.url, '_blank');
            });
        });
    </script>
</x-client-layout>
