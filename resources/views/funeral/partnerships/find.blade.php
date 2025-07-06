<x-layouts.funeral>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('funeral.partnerships.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Partnerships
            </a>
            <h2 class="text-white mb-0">Find Partners</h2>
            <div></div> {{-- For alignment --}}
        </div>

        <!-- Search form -->
        <form method="GET" action="{{ route('funeral.partnerships.find') }}" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search funeral parlors by name" value="{{ $search ?? '' }}">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>

        <!-- Flash messages -->
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Potential partners list -->
        <div class="card bg-dark border-0 shadow mb-2">
            <div class="card-header bg-primary text-white fw-semibold">
                Available Funeral Parlors
            </div>
            <div class="card-body p-0">
@if($potentialPartners->isEmpty())
    <div class="p-4 text-center text-white">
        <i class="bi bi-emoji-frown fs-4"></i>
        <div>No available partners found.</div>
    </div>
@else
                    <ul class="list-group list-group-flush">
                        @foreach($potentialPartners as $partner)
                            @php
                                $parlorInfo = $partner->funeralParlor ?? null;
                            @endphp
                            <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center px-3 py-3">
                                <span>
                                    <span
                                        style="cursor:pointer; text-decoration: underline;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#partnerModal-{{ $partner->id }}"
                                    >{{ $partner->name }}</span>
                                    @if($parlorInfo && $parlorInfo->address)
                                        <small class="text-muted ms-2"><i class="bi bi-geo-alt"></i> {{ $parlorInfo->address }}</small>
                                    @endif
                                </span>
                                <form method="POST" action="{{ route('funeral.partnerships.request') }}">
                                    @csrf
                                    <input type="hidden" name="partner_id" value="{{ $partner->id }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-person-plus"></i> Send Request
                                    </button>
                                </form>
                            </li>

                            <!-- Partner Modal (One per partner) -->
                            <div class="modal fade" id="partnerModal-{{ $partner->id }}" tabindex="-1" aria-labelledby="partnerModalLabel-{{ $partner->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content bg-dark text-white">
                                        <div class="modal-header border-secondary">
                                            <h5 class="modal-title" id="partnerModalLabel-{{ $partner->id }}">
                                                <i class="bi bi-building"></i> Funeral Parlor Details
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if($parlorInfo)
                                                <div class="row g-3 align-items-center">
                                                    <div class="col-md-4 text-center">
                                                        @if($parlorInfo->image)
                                                            <img src="{{ asset('storage/' . $parlorInfo->image) }}"
                                                                class="img-fluid rounded shadow"
                                                                style="max-height: 150px;"
                                                                alt="Partner Logo">
                                                        @else
                                                            <div class="bg-secondary text-white py-4 rounded">
                                                                <i class="bi bi-image fs-1"></i>
                                                                <div>No Image</div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-8">
                                                        <dl class="row">
                                                            <dt class="col-sm-4">Name:</dt>
                                                            <dd class="col-sm-8">{{ $partner->name }}</dd>
                                                            <dt class="col-sm-4">Address:</dt>
                                                            <dd class="col-sm-8">{{ $parlorInfo->address ?? '-' }}</dd>
                                                            <dt class="col-sm-4">Email:</dt>
                                                            <dd class="col-sm-8">{{ $parlorInfo->contact_email ?? '-' }}</dd>
                                                            <dt class="col-sm-4">Contact #:</dt>
                                                            <dd class="col-sm-8">{{ $parlorInfo->contact_number ?? '-' }}</dd>
                                                            <dt class="col-sm-4">Description:</dt>
                                                            <dd class="col-sm-8">{{ $parlorInfo->description ?? '-' }}</dd>
                                                        </dl>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="alert alert-warning">
                                                    No detailed info for this funeral parlor yet.
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer border-secondary">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Modal -->
                        @endforeach
                    </ul>
                    <div class="p-3">
                        {{ $potentialPartners->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.funeral>
