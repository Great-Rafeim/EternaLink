<x-layouts.funeral>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-white mb-0">Partnerships</h2>
        <div>
            <a href="{{ route('funeral.partnerships.resource_requests.index') }}" class="btn btn-warning me-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                View Resource Requests
            </a>
            <a href="{{ route('funeral.partnerships.find') }}" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Find Partners
            </a>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4" id="partnershipTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="active-tab" data-bs-toggle="pill" href="#active" role="tab" aria-controls="active" aria-selected="true">
                <i class="bi bi-link-45deg me-1"></i> My Partnerships
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="sent-tab" data-bs-toggle="pill" href="#sent" role="tab" aria-controls="sent" aria-selected="false">
                <i class="bi bi-send-check me-1"></i> Sent Requests
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="incoming-tab" data-bs-toggle="pill" href="#incoming" role="tab" aria-controls="incoming" aria-selected="false">
                <i class="bi bi-inbox me-1"></i> Incoming Requests
            </a>
        </li>
    </ul>

    <!-- Collect unique partners for modal rendering -->
    @php
        $partnerModals = [];
        function addPartnerModal(&$modals, $user, $parlor = null) {
            if ($user && !isset($modals[$user->id])) {
                $modals[$user->id] = [
                    'user' => $user,
                    'parlor' => $parlor ?? ($user->funeralParlor ?? null)
                ];
            }
        }
    @endphp

    <div class="tab-content" id="partnershipTabContent">
        <!-- Active Partnerships -->
        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <strong>Active Partnerships</strong>
                </div>
                <div class="card-body bg-dark text-white">
                    @if($activePartnerships->isEmpty())
                        <p>No active partnerships yet.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($activePartnerships as $partnership)
                                @php
                                    $isRequester = $partnership->requester_id == auth()->id();
                                    $partnerUser = $isRequester ? $partnership->partner : $partnership->requester;
                                    $parlorInfo = $partnerUser->funeralParlor ?? null;
                                    addPartnerModal($partnerModals, $partnerUser, $parlorInfo);
                                @endphp
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                                    @if($partnerUser)
                                        <span
                                            style="cursor:pointer; text-decoration: underline;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#partnerModal-{{ $partnerUser->id }}"
                                        >{{ $partnerUser->name }}</span>
                                        @if($parlorInfo && $parlorInfo->address)
                                            <small class="text-muted ms-2"><i class="bi bi-geo-alt"></i> {{ $parlorInfo->address }}</small>
                                        @endif
                                    @else
                                        <span class="badge bg-danger">Deleted Funeral Parlor</span>
                                    @endif
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success">Accepted</span>
                                        <form method="POST" action="{{ route('funeral.partnerships.destroy', $partnership->id) }}"
                                            onsubmit="return confirm('Dissolve this partnership?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Dissolve</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sent Requests -->
        <div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <strong>Sent Requests</strong>
                </div>
                <div class="card-body bg-dark text-white">
                    @if($sentRequests->isEmpty())
                        <p>No partnership requests sent.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($sentRequests as $partnership)
                                @php
                                    $partnerUser = $partnership->partner;
                                    $parlorInfo = $partnerUser->funeralParlor ?? null;
                                    addPartnerModal($partnerModals, $partnerUser, $parlorInfo);
                                @endphp
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                                    @if($partnerUser)
                                        <span
                                            style="cursor:pointer; text-decoration: underline;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#partnerModal-{{ $partnerUser->id }}"
                                        >{{ $partnerUser->name }}</span>
                                    @else
                                        <span class="badge bg-danger">Deleted Funeral Parlor</span>
                                    @endif
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary text-capitalize">{{ $partnership->status }}</span>
                                        @if($partnership->status === 'pending')
                                            <form method="POST" action="{{ route('funeral.partnerships.destroy', $partnership->id) }}"
                                                onsubmit="return confirm('Cancel this request?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <!-- Incoming Requests -->
        <div class="tab-pane fade" id="incoming" role="tabpanel" aria-labelledby="incoming-tab">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <strong>Incoming Requests</strong>
                </div>
                <div class="card-body bg-dark text-white">
                    @if($receivedRequests->isEmpty())
                        <p>No incoming partnership requests.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($receivedRequests as $partnership)
                                @php
                                    $partnerUser = $partnership->requester;
                                    $parlorInfo = $partnerUser->funeralParlor ?? null;
                                    addPartnerModal($partnerModals, $partnerUser, $parlorInfo);
                                @endphp
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                                    <div>
                                        @if($partnerUser)
                                            <span
                                                style="cursor:pointer; text-decoration: underline;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#partnerModal-{{ $partnerUser->id }}"
                                            >From: {{ $partnerUser->name }}</span>
                                        @else
                                            <span class="badge bg-danger">Deleted Funeral Parlor</span>
                                        @endif
                                        <span class="badge bg-warning text-dark">{{ $partnership->status }}</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form method="POST" action="{{ route('funeral.partnerships.respond', $partnership->id) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="accept">
                                            <button class="btn btn-sm btn-success" onclick="return confirm('Accept this partnership request?');">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('funeral.partnerships.respond', $partnership->id) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Reject this partnership request?');">Reject</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Render all unique modals at the bottom -->
    @foreach($partnerModals as $partnerId => $data)
        @php
            $partner = $data['user'];
            $parlor = $data['parlor'];
        @endphp
        <div class="modal fade" id="partnerModal-{{ $partner->id }}" tabindex="-1" aria-labelledby="partnerModalLabel-{{ $partner->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="partnerModalLabel-{{ $partner->id }}">
                            <i class="bi bi-building"></i>
                            Partner Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($parlor)
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4 text-center">
                                    @if($parlor->image)
                                        <img src="{{ asset('storage/' . $parlor->image) }}"
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
                                        <dd class="col-sm-8">{{ $parlor->address ?? '-' }}</dd>
                                        <dt class="col-sm-4">Email:</dt>
                                        <dd class="col-sm-8">{{ $parlor->contact_email ?? '-' }}</dd>
                                        <dt class="col-sm-4">Contact #:</dt>
                                        <dd class="col-sm-8">{{ $parlor->contact_number ?? '-' }}</dd>
                                        <dt class="col-sm-4">Description:</dt>
                                        <dd class="col-sm-8">{{ $parlor->description ?? '-' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                No detailed info for this partner yet.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</x-layouts.funeral>
