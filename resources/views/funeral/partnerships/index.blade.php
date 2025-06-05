<x-layouts.funeral>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white mb-0">Partnerships</h2>
            <a href="{{ route('funeral.partnerships.find') }}" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Find Partners
            </a>
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

        <!-- Tab Panes -->
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
                                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                                        {{ $partnership->requester_id == auth()->id() 
                                            ? $partnership->partner->name 
                                            : $partnership->requester->name }}
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
                                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                                        To: {{ $partnership->partner->name }}
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
                                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                                        <div>
                                            From: {{ $partnership->requester->name }}
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
    </div>
</x-layouts.funeral>

<!-- Make sure Bootstrap JS is loaded for the tabs to work -->
