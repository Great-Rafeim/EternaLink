<x-layouts.funeral>
    <div class="container-fluid py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="card bg-dark text-white shadow rounded-4">
                    <div class="card-body">
                        <h5 class="mb-4"><i class="bi bi-inbox me-2"></i> Resource Requests</h5>
                        <ul class="nav flex-column nav-pills" id="sidebarNav" role="tablist">
                            <li class="nav-item mb-2">
                                <button class="nav-link active w-100" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                                    <i class="bi bi-hourglass-split me-1"></i> Pending / Active
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link w-100" id="finished-tab" data-bs-toggle="pill" data-bs-target="#finished" type="button" role="tab" aria-controls="finished" aria-selected="false">
                                    <i class="bi bi-archive me-1"></i> Finished Transactions
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Search Bar -->
                <form method="GET" class="mb-4">
                    <div class="input-group shadow-sm">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control rounded-start-pill" placeholder="🔍 Search requests by item, partner, or status...">
                        <button class="btn btn-primary rounded-end-pill px-4 fw-semibold" type="submit">Search</button>
                    </div>
                </form>

                <div class="tab-content" id="sidebarTabContent">
                    <!-- Pending/Active -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="row g-4">
                            <!-- Sent Requests -->
                            <div class="col-12">
                                <div class="card shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-primary text-white rounded-top-4">
                                        <strong><i class="bi bi-send"></i> Requests I Sent</strong>
                                    </div>
                                    <div class="card-body bg-dark text-white">
                                        @php
                                            $sent = $sentRequests->filter(function($r) {
                                                return in_array($r->status, ['pending','approved']);
                                            });
                                            if (request('search')) {
                                                $sent = $sent->filter(function($r) {
                                                    $needle = strtolower(request('search'));
                                                    return str_contains(strtolower($r->requestedItem->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->provider->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->status), $needle);
                                                });
                                            }
                                        @endphp
                                        @if($sent->isEmpty())
                                            <div class="alert alert-info mb-0">No pending or active requests sent.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover table-dark table-striped align-middle rounded">
                                                    <thead class="table-light text-dark">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>To</th>
                                                            <th>Item Requested</th>
                                                            <th>Qty</th>
                                                            <th>Date Needed</th>
                                                            <th>Status</th>
                                                            <th>Requested On</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($sent as $request)
                                                            <tr>
                                                                <td>{{ $request->id }}</td>
                                                                <td>{{ $request->provider->name ?? '-' }}</td>
                                                                <td>
                                                                    <strong>{{ $request->requestedItem->name ?? '-' }}</strong>
                                                                    <span class="d-block small text-muted">
                                                                        From: {{ $request->providerItem->name ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->quantity }}</td>
                                                                <td>
                                                                    {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                                </td>
                                                                <td>
                                                                    <span class="badge
                                                                        @if($request->status == 'pending') bg-warning text-dark
                                                                        @elseif($request->status == 'approved') bg-success
                                                                        @else bg-secondary @endif">
                                                                        {{ ucfirst($request->status) }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-outline-light"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#requestModal"
                                                                        data-type="sent"
                                                                        data-request='@json($request)'
                                                                        data-requester="{{ $request->requester->name ?? '-' }}"
                                                                        data-provider="{{ $request->provider->name ?? '-' }}"
                                                                        data-item="{{ $request->requestedItem->name ?? '-' }}"
                                                                        data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                        data-quantity="{{ $request->quantity }}"
                                                                        data-status="{{ ucfirst($request->status) }}"
                                                                        data-date="{{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}"
                                                                        data-method="{{ $request->delivery_method }}"
                                                                        data-contactname="{{ $request->contact_name }}"
                                                                        data-contactemail="{{ $request->contact_email }}"
                                                                        data-contactmobile="{{ $request->contact_mobile }}"
                                                                        data-notes="{{ $request->notes }}"
                                                                    >
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>
                                                                    @if($request->status === 'pending')
                                                                        <form action="{{ route('funeral.partnerships.resource_requests.cancel', $request->id) }}" method="POST" style="display:inline;">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this request?')">
                                                                                <i class="bi bi-x"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                    @if($request->status === 'approved')
                                                                        <button class="btn btn-sm btn-primary"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#receiveModal"
                                                                            data-request-id="{{ $request->id }}"
                                                                            data-provider-item-id="{{ $request->provider_item_id }}"
                                                                            data-quantity="{{ $request->quantity }}"
                                                                            data-item-id="{{ $request->requested_item_id }}"
                                                                        >
                                                                            <i class="bi bi-box-arrow-in-down"></i> Receive
                                                                        </button>
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
                            </div>
                            <!-- Received Requests -->
                            <div class="col-12">
                                <div class="card shadow-sm rounded-4">
                                    <div class="card-header bg-info text-white rounded-top-4">
                                        <strong><i class="bi bi-box-arrow-in-down"></i> Requests to Me</strong>
                                    </div>
                                    <div class="card-body bg-dark text-white">
                                        @php
                                            $received = $receivedRequests->filter(function($r) {
                                                return in_array($r->status, ['pending','approved']);
                                            });
                                            if (request('search')) {
                                                $received = $received->filter(function($r) {
                                                    $needle = strtolower(request('search'));
                                                    return str_contains(strtolower($r->providerItem->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->requester->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->status), $needle);
                                                });
                                            }
                                        @endphp
                                        @if($received->isEmpty())
                                            <div class="alert alert-info mb-0">No pending or active requests to you.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover table-dark table-striped align-middle rounded">
                                                    <thead class="table-light text-dark">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>From</th>
                                                            <th>Requested Item</th>
                                                            <th>Qty</th>
                                                            <th>Date Needed</th>
                                                            <th>Status</th>
                                                            <th>Requested On</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($received as $request)
                                                            <tr>
                                                                <td>{{ $request->id }}</td>
                                                                <td>{{ $request->requester->name ?? '-' }}</td>
                                                                <td>
                                                                    <strong>{{ $request->providerItem->name ?? '-' }}</strong>
                                                                    <span class="d-block small text-muted">
                                                                        For: {{ $request->requestedItem->name ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->quantity }}</td>
                                                                <td>
                                                                    {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                                </td>
                                                                <td>
                                                                    <span class="badge
                                                                        @if($request->status == 'pending') bg-warning text-dark
                                                                        @elseif($request->status == 'approved') bg-success
                                                                        @else bg-secondary @endif">
                                                                        {{ ucfirst($request->status) }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-outline-light"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#requestModal"
                                                                        data-type="received"
                                                                        data-request='@json($request)'
                                                                        data-requester="{{ $request->requester->name ?? '-' }}"
                                                                        data-provider="{{ $request->provider->name ?? '-' }}"
                                                                        data-item="{{ $request->requestedItem->name ?? '-' }}"
                                                                        data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                        data-quantity="{{ $request->quantity }}"
                                                                        data-status="{{ ucfirst($request->status) }}"
                                                                        data-date="{{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}"
                                                                        data-method="{{ $request->delivery_method }}"
                                                                        data-contactname="{{ $request->contact_name }}"
                                                                        data-contactemail="{{ $request->contact_email }}"
                                                                        data-contactmobile="{{ $request->contact_mobile }}"
                                                                        data-notes="{{ $request->notes }}"
                                                                    >
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>
                                                                    @if($request->status == 'pending')
                                                                        <form action="{{ route('funeral.partnerships.resource_requests.approve', $request->id) }}" method="POST" style="display:inline;">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <button class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve this request?')">
                                                                                <i class="bi bi-check"></i>
                                                                            </button>
                                                                        </form>
                                                                        <form action="{{ route('funeral.partnerships.resource_requests.reject', $request->id) }}" method="POST" style="display:inline;">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <button class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this request?')">
                                                                                <i class="bi bi-x"></i>
                                                                            </button>
                                                                        </form>
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
                            </div>
                        </div>
                    </div>

                    <!-- Finished Transactions -->
                    <div class="tab-pane fade" id="finished" role="tabpanel" aria-labelledby="finished-tab">
                        <div class="row g-4">
                            <!-- Sent Finished -->
                            <div class="col-12">
                                <div class="card shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-secondary text-white rounded-top-4">
                                        <strong><i class="bi bi-send"></i> Sent (Completed/Rejected/Cancelled)</strong>
                                    </div>
                                    <div class="card-body bg-dark text-white">
                                        @php
                                            $finishedSent = $sentRequests->filter(function($r) {
                                                return in_array($r->status, ['fulfilled','rejected','cancelled']);
                                            });
                                            if (request('search')) {
                                                $finishedSent = $finishedSent->filter(function($r) {
                                                    $needle = strtolower(request('search'));
                                                    return str_contains(strtolower($r->requestedItem->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->provider->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->status), $needle);
                                                });
                                            }
                                        @endphp
                                        @if($finishedSent->isEmpty())
                                            <div class="alert alert-info mb-0">No finished sent requests.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover table-dark table-striped align-middle rounded">
                                                    <thead class="table-light text-dark">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>To</th>
                                                            <th>Item Requested</th>
                                                            <th>Qty</th>
                                                            <th>Date Needed</th>
                                                            <th>Status</th>
                                                            <th>Requested On</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($finishedSent as $request)
                                                            <tr>
                                                                <td>{{ $request->id }}</td>
                                                                <td>{{ $request->provider->name ?? '-' }}</td>
                                                                <td>
                                                                    <strong>{{ $request->requestedItem->name ?? '-' }}</strong>
                                                                    <span class="d-block small text-muted">
                                                                        From: {{ $request->providerItem->name ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->quantity }}</td>
                                                                <td>
                                                                    {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                                </td>
                                                                <td>
                                                                    <span class="badge
                                                                        @if($request->status == 'fulfilled') bg-primary
                                                                        @elseif($request->status == 'rejected') bg-danger
                                                                        @elseif($request->status == 'cancelled') bg-secondary
                                                                        @else bg-light text-dark
                                                                        @endif">
                                                                        {{ ucfirst($request->status) }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-outline-light"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#requestModal"
                                                                        data-type="sent"
                                                                        data-request='@json($request)'
                                                                        data-requester="{{ $request->requester->name ?? '-' }}"
                                                                        data-provider="{{ $request->provider->name ?? '-' }}"
                                                                        data-item="{{ $request->requestedItem->name ?? '-' }}"
                                                                        data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                        data-quantity="{{ $request->quantity }}"
                                                                        data-status="{{ ucfirst($request->status) }}"
                                                                        data-date="{{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}"
                                                                        data-method="{{ $request->delivery_method }}"
                                                                        data-contactname="{{ $request->contact_name }}"
                                                                        data-contactemail="{{ $request->contact_email }}"
                                                                        data-contactmobile="{{ $request->contact_mobile }}"
                                                                        data-notes="{{ $request->notes }}"
                                                                    >
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- Received Finished -->
                            <div class="col-12">
                                <div class="card shadow-sm rounded-4">
                                    <div class="card-header bg-secondary text-white rounded-top-4">
                                        <strong><i class="bi bi-box-arrow-in-down"></i> Received (Completed/Rejected/Cancelled)</strong>
                                    </div>
                                    <div class="card-body bg-dark text-white">
                                        @php
                                            $finishedReceived = $receivedRequests->filter(function($r) {
                                                return in_array($r->status, ['fulfilled','rejected','cancelled']);
                                            });
                                            if (request('search')) {
                                                $finishedReceived = $finishedReceived->filter(function($r) {
                                                    $needle = strtolower(request('search'));
                                                    return str_contains(strtolower($r->providerItem->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->requester->name ?? ''), $needle)
                                                        || str_contains(strtolower($r->status), $needle);
                                                });
                                            }
                                        @endphp
                                        @if($finishedReceived->isEmpty())
                                            <div class="alert alert-info mb-0">No finished received requests.</div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover table-dark table-striped align-middle rounded">
                                                    <thead class="table-light text-dark">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>From</th>
                                                            <th>Requested Item</th>
                                                            <th>Qty</th>
                                                            <th>Date Needed</th>
                                                            <th>Status</th>
                                                            <th>Requested On</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($finishedReceived as $request)
                                                            <tr>
                                                                <td>{{ $request->id }}</td>
                                                                <td>{{ $request->requester->name ?? '-' }}</td>
                                                                <td>
                                                                    <strong>{{ $request->providerItem->name ?? '-' }}</strong>
                                                                    <span class="d-block small text-muted">
                                                                        For: {{ $request->requestedItem->name ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->quantity }}</td>
                                                                <td>
                                                                    {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                                </td>
                                                                <td>
                                                                    <span class="badge
                                                                        @if($request->status == 'fulfilled') bg-primary
                                                                        @elseif($request->status == 'rejected') bg-danger
                                                                        @elseif($request->status == 'cancelled') bg-secondary
                                                                        @else bg-light text-dark
                                                                        @endif">
                                                                        {{ ucfirst($request->status) }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $request->created_at->format('Y-m-d') }}</td>
                                                                <td>
                                                                    <button class="btn btn-sm btn-outline-light"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#requestModal"
                                                                        data-type="received"
                                                                        data-request='@json($request)'
                                                                        data-requester="{{ $request->requester->name ?? '-' }}"
                                                                        data-provider="{{ $request->provider->name ?? '-' }}"
                                                                        data-item="{{ $request->requestedItem->name ?? '-' }}"
                                                                        data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                        data-quantity="{{ $request->quantity }}"
                                                                        data-status="{{ ucfirst($request->status) }}"
                                                                        data-date="{{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}"
                                                                        data-method="{{ $request->delivery_method }}"
                                                                        data-contactname="{{ $request->contact_name }}"
                                                                        data-contactemail="{{ $request->contact_email }}"
                                                                        data-contactmobile="{{ $request->contact_mobile }}"
                                                                        data-notes="{{ $request->notes }}"
                                                                    >
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Request Details -->
        <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header">
                        <h5 class="modal-title" id="requestModalLabel">Resource Request Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <span id="modal-status"></span></p>
                                <p><strong>Requester:</strong> <span id="modal-requester"></span></p>
                                <p><strong>Provider:</strong> <span id="modal-provider"></span></p>
                                <p><strong>Item Needed:</strong> <span id="modal-item"></span></p>
                                <p><strong>Provider's Item:</strong> <span id="modal-provideritem"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Quantity:</strong> <span id="modal-quantity"></span></p>
                                <p><strong>Preferred Date:</strong> <span id="modal-date"></span></p>
                                <p><strong>Delivery Method:</strong> <span id="modal-method"></span></p>
                                <p><strong>Contact Name:</strong> <span id="modal-contactname"></span></p>
                                <p><strong>Contact Email:</strong> <span id="modal-contactemail"></span></p>
                                <p><strong>Contact Mobile:</strong> <span id="modal-contactmobile"></span></p>
                            </div>
                            <div class="col-12">
                                <p><strong>Notes:</strong> <span id="modal-notes"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="receiveModal" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" id="receiveForm">
                    @csrf
                    @method('PATCH')
                    <div class="modal-content bg-dark text-white">
                        <div class="modal-header">
                            <h5 class="modal-title" id="receiveModalLabel">Confirm Receipt</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>
                                Are you sure you have received the item(s)?<br>
                                <span class="fw-bold text-info">Once confirmed, the provider’s shareable quantity will be reduced, and your stock will be increased accordingly.</span>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Yes, I have received it</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>




    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var receiveModal = document.getElementById('receiveModal');
        receiveModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-request-id');
            var form = document.getElementById('receiveForm');
            form.action = '/funeral/resource-requests/' + requestId + '/fulfill';
        });
    });
    </script>


    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('requestModal');
        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('modal-status').textContent = button.getAttribute('data-status');
            document.getElementById('modal-requester').textContent = button.getAttribute('data-requester');
            document.getElementById('modal-provider').textContent = button.getAttribute('data-provider');
            document.getElementById('modal-item').textContent = button.getAttribute('data-item');
            document.getElementById('modal-provideritem').textContent = button.getAttribute('data-provideritem');
            document.getElementById('modal-quantity').textContent = button.getAttribute('data-quantity');
            document.getElementById('modal-date').textContent = button.getAttribute('data-date');
            document.getElementById('modal-method').textContent = button.getAttribute('data-method');
            document.getElementById('modal-contactname').textContent = button.getAttribute('data-contactname');
            document.getElementById('modal-contactemail').textContent = button.getAttribute('data-contactemail');
            document.getElementById('modal-contactmobile').textContent = button.getAttribute('data-contactmobile');
            document.getElementById('modal-notes').textContent = button.getAttribute('data-notes');
        });
    });
    </script>



</x-layouts.funeral>
