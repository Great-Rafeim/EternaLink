<x-layouts.funeral>
<div class="container-fluid py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="card bg-gradient-dark text-white shadow rounded-4 border-0">
                <div class="card-body">
                    <h5 class="mb-4 fw-bold"><i class="bi bi-inbox me-2"></i> Resource Requests</h5>
                    <a href="{{ route('funeral.partnerships.resource_requests.browse') }}"
                        class="btn btn-success w-100 mb-3 fw-semibold rounded-pill shadow">
                        <i class="bi bi-search me-1"></i> Browse Partners' Shareables
                    </a>
                    <ul class="nav flex-column nav-pills" id="sidebarNav" role="tablist">
                        <li class="nav-item mb-2">
                            <button class="nav-link active w-100 rounded-3" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                                <i class="bi bi-hourglass-split me-1"></i> Pending / Active
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link w-100 rounded-3" id="finished-tab" data-bs-toggle="pill" data-bs-target="#finished" type="button" role="tab" aria-controls="finished" aria-selected="false">
                                <i class="bi bi-archive me-1"></i> Finished
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
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control rounded-start-pill bg-light border-0" placeholder="🔍 Search requests...">
                    <button class="btn btn-primary rounded-end-pill px-4 fw-semibold" type="submit">Search</button>
                </div>
            </form>
            <div class="tab-content" id="sidebarTabContent">
                <!-- Pending/Active -->
                <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <div class="row g-4">
                        <!-- Requests I Sent -->
                        <div class="col-12">
                            <div class="card shadow rounded-4 mb-4 border-0">
                                <div class="card-header bg-primary text-white rounded-top-4 fw-semibold">
                                    <i class="bi bi-send"></i> Requests I Sent
                                </div>
                                <div class="card-body bg-gradient-dark text-white rounded-bottom-4">
@php
    $sent = $sentRequestsActive;
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
                                            <table class="table table-hover align-middle table-dark table-striped mb-0">
                                                <thead class="table-light text-dark">
                                                    <tr>
                                                        <th>To</th>
                                                        <th>Item</th>
                                                        <th>Qty</th>
                                                        <th>Date Needed</th>
                                                        <th>Status</th>
                                                        <th>Requested</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($sent as $request)
                                                    @php
                                                        $isAsset = $request->providerItem && $request->providerItem->category && $request->providerItem->category->is_asset;
                                                    @endphp
                                                    <tr class="align-middle">
                                                        <td>{{ $request->provider->name ?? '-' }}</td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $request->requestedItem->name ?? '-' }}</span>
                                                            <br>
                                                            <span class="text-info small">From: {{ $request->providerItem->name ?? '-' }}</span>
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <span class="badge rounded-pill bg-secondary px-3">Asset</span>
                                                            @else
                                                                {{ $request->quantity }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <i class="bi bi-calendar2-check"></i>
                                                                {{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-' }}
                                                            @else
                                                                {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill
                                                                @if($request->status == 'pending') bg-warning text-dark
                                                                @elseif($request->status == 'approved') bg-success
                                                                @else bg-secondary @endif
                                                            ">
                                                                {{ ucfirst($request->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="small">{{ $request->created_at->format('Y-m-d') }}</span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info px-2"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#requestModal"
                                                                data-status="{{ ucfirst($request->status) }}"
                                                                data-requester="{{ $request->requester->name ?? '-' }}"
                                                                data-provider="{{ $request->provider->name ?? '-' }}"
                                                                data-requesteditem="{{ $request->requestedItem->name ?? '-' }}"
                                                                data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                data-is-asset="{{ $isAsset ? 1 : 0 }}"
                                                                data-quantity="{{ $request->quantity ?? '-' }}"
                                                                data-date="{{ $isAsset ? ($request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-') : ($request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-') }}"
                                                                data-reserved-start="{{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d H:i') : '-' }}"
                                                                data-reserved-end="{{ $request->reserved_end ? \Carbon\Carbon::parse($request->reserved_end)->format('Y-m-d H:i') : '-' }}"
                                                                data-method="{{ $request->delivery_method ?? '-' }}"
                                                                data-contactname="{{ $request->contact_name ?? '-' }}"
                                                                data-contactemail="{{ $request->contact_email ?? '-' }}"
                                                                data-contactmobile="{{ $request->contact_mobile ?? '-' }}"
                                                                data-notes="{{ $request->notes ?? '-' }}"
                                                            >
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            @if($request->status === 'pending')
                                                                <form action="{{ route('funeral.partnerships.resource_requests.cancel', $request->id) }}" method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button class="btn btn-sm btn-outline-danger px-2" title="Cancel" onclick="return confirm('Cancel this request?')">
                                                                        <i class="bi bi-x"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if($request->status === 'approved')
<!-- Example usage in your table/list -->
<button type="button"
    class="btn btn-success"
    data-bs-toggle="modal"
    data-bs-target="#receiveModal"
    data-request-id="{{ $request->id }}"
    data-is-asset="{{ $request->providerItem->category->is_asset ?? 0 }}">
    <i class="bi bi-box-arrow-in-down"></i>
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
                        <!-- Requests to Me -->
                        <div class="col-12">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-header bg-info text-white rounded-top-4 fw-semibold">
                                    <i class="bi bi-box-arrow-in-down"></i> Requests to Me
                                </div>
                                <div class="card-body bg-gradient-dark text-white rounded-bottom-4">
@php
    $received = $receivedRequestsActive;
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
                                            <table class="table table-hover align-middle table-dark table-striped mb-0">
                                                <thead class="table-light text-dark">
                                                    <tr>
                                                        <th>From</th>
                                                        <th>Item</th>
                                                        <th>Qty</th>
                                                        <th>Date Needed</th>
                                                        <th>Status</th>
                                                        <th>Requested</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($received as $request)
                                                    @php
                                                        $isAsset = $request->providerItem && $request->providerItem->category && $request->providerItem->category->is_asset;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $request->requester->name ?? '-' }}</td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $request->providerItem->name ?? '-' }}</span>
                                                            <br>
                                                            <span class="text-info small">For: {{ $request->requestedItem->name ?? '-' }}</span>
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <span class="badge rounded-pill bg-secondary px-3">Asset</span>
                                                            @else
                                                                {{ $request->quantity }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <i class="bi bi-calendar2-check"></i>
                                                                {{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-' }}
                                                            @else
                                                                {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill
                                                                @if($request->status == 'pending') bg-warning text-dark
                                                                @elseif($request->status == 'approved') bg-success
                                                                @else bg-secondary @endif
                                                            ">
                                                                {{ ucfirst($request->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="small">{{ $request->created_at->format('Y-m-d') }}</span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info px-2"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#requestModal"
                                                                data-status="{{ ucfirst($request->status) }}"
                                                                data-requester="{{ $request->requester->name ?? '-' }}"
                                                                data-provider="{{ $request->provider->name ?? '-' }}"
                                                                data-requesteditem="{{ $request->requestedItem->name ?? '-' }}"
                                                                data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                data-is-asset="{{ $isAsset ? 1 : 0 }}"
                                                                data-quantity="{{ $request->quantity ?? '-' }}"
                                                                data-date="{{ $isAsset ? ($request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-') : ($request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-') }}"
                                                                data-reserved-start="{{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d H:i') : '-' }}"
                                                                data-reserved-end="{{ $request->reserved_end ? \Carbon\Carbon::parse($request->reserved_end)->format('Y-m-d H:i') : '-' }}"
                                                                data-method="{{ $request->delivery_method ?? '-' }}"
                                                                data-contactname="{{ $request->contact_name ?? '-' }}"
                                                                data-contactemail="{{ $request->contact_email ?? '-' }}"
                                                                data-contactmobile="{{ $request->contact_mobile ?? '-' }}"
                                                                data-notes="{{ $request->notes ?? '-' }}"
                                                            >
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            @if($request->status === 'pending')
                                                                <button class="btn btn-sm btn-success approve-btn px-2"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#approveModal"
                                                                    data-request-id="{{ $request->id }}"
                                                                    data-is-asset="{{ $isAsset ? 1 : 0 }}"
                                                                    data-reserved-start="{{ $request->reserved_start ?? '' }}"
                                                                    data-reserved-end="{{ $request->reserved_end ?? '' }}"
                                                                    data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                    data-requesteditem="{{ $request->requestedItem->name ?? '-' }}"
                                                                    data-quantity="{{ $request->quantity }}"
                                                                >
                                                                    <i class="bi bi-check"></i>
                                                                </button>
                                                                <form action="{{ route('funeral.partnerships.resource_requests.reject', $request->id) }}" method="POST" style="display:inline;">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button class="btn btn-sm btn-outline-danger px-2" title="Reject" onclick="return confirm('Reject this request?')">
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
                            <div class="card shadow rounded-4 mb-4 border-0">
                                <div class="card-header bg-secondary text-white rounded-top-4 fw-semibold">
                                    <i class="bi bi-send"></i> Sent (Completed/Rejected/Cancelled)
                                </div>
                                <div class="card-body bg-gradient-dark text-white rounded-bottom-4">
@php
    $finishedSent = $sentRequestsFinished;
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
                                            <table class="table table-hover align-middle table-dark table-striped mb-0">
                                                <thead class="table-light text-dark">
                                                    <tr>
                                                        <th>To</th>
                                                        <th>Item</th>
                                                        <th>Qty</th>
                                                        <th>Date Needed</th>
                                                        <th>Status</th>
                                                        <th>Requested</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($finishedSent as $request)
                                                    @php
                                                        $isAsset = $request->providerItem && $request->providerItem->category && $request->providerItem->category->is_asset;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $request->provider->name ?? '-' }}</td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $request->requestedItem->name ?? '-' }}</span>
                                                            <br>
                                                            <span class="text-info small">From: {{ $request->providerItem->name ?? '-' }}</span>
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <span class="badge rounded-pill bg-secondary px-3">Asset</span>
                                                            @else
                                                                {{ $request->quantity }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <i class="bi bi-calendar2-check"></i>
                                                                {{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-' }}
                                                            @else
                                                                {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill
                                                                @if($request->status == 'fulfilled') bg-primary
                                                                @elseif($request->status == 'rejected') bg-danger
                                                                @elseif($request->status == 'cancelled') bg-secondary
                                                                @else bg-light text-dark @endif">
                                                                {{ ucfirst($request->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="small">{{ $request->created_at->format('Y-m-d') }}</span>
                                                        </td>
                                                        <td>
                                                            <button
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#requestModal"
                                                                class="btn btn-sm btn-outline-info px-2"
                                                                data-status="{{ ucfirst($request->status) }}"
                                                                data-requester="{{ $request->requester->name ?? '-' }}"
                                                                data-provider="{{ $request->provider->name ?? '-' }}"
                                                                data-requesteditem="{{ $request->requestedItem->name ?? '-' }}"
                                                                data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                data-is-asset="{{ $isAsset ? 1 : 0 }}"
                                                                data-quantity="{{ $request->quantity ?? '-' }}"
                                                                data-date="{{ $isAsset ? ($request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-') : ($request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-') }}"
                                                                data-reserved-start="{{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d H:i') : '-' }}"
                                                                data-reserved-end="{{ $request->reserved_end ? \Carbon\Carbon::parse($request->reserved_end)->format('Y-m-d H:i') : '-' }}"
                                                                data-method="{{ $request->delivery_method ?? '-' }}"
                                                                data-contactname="{{ $request->contact_name ?? '-' }}"
                                                                data-contactemail="{{ $request->contact_email ?? '-' }}"
                                                                data-contactmobile="{{ $request->contact_mobile ?? '-' }}"
                                                                data-notes="{{ $request->notes ?? '-' }}"
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
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-header bg-secondary text-white rounded-top-4 fw-semibold">
                                    <i class="bi bi-box-arrow-in-down"></i> Received (Completed/Rejected/Cancelled)
                                </div>
                                <div class="card-body bg-gradient-dark text-white rounded-bottom-4">
@php
    $finishedReceived = $receivedRequestsFinished;
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
                                            <table class="table table-hover align-middle table-dark table-striped mb-0">
                                                <thead class="table-light text-dark">
                                                    <tr>
                                                        <th>From</th>
                                                        <th>Item</th>
                                                        <th>Qty</th>
                                                        <th>Date Needed</th>
                                                        <th>Status</th>
                                                        <th>Requested</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($finishedReceived as $request)
                                                    @php
                                                        $isAsset = $request->providerItem && $request->providerItem->category && $request->providerItem->category->is_asset;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $request->requester->name ?? '-' }}</td>
                                                        <td>
                                                            <span class="fw-semibold">{{ $request->providerItem->name ?? '-' }}</span>
                                                            <br>
                                                            <span class="text-info small">For: {{ $request->requestedItem->name ?? '-' }}</span>
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <span class="badge rounded-pill bg-secondary px-3">Asset</span>
                                                            @else
                                                                {{ $request->quantity }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($isAsset)
                                                                <i class="bi bi-calendar2-check"></i>
                                                                {{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-' }}
                                                            @else
                                                                {{ $request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-' }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill
                                                                @if($request->status == 'fulfilled') bg-primary
                                                                @elseif($request->status == 'rejected') bg-danger
                                                                @elseif($request->status == 'cancelled') bg-secondary
                                                                @else bg-light text-dark @endif">
                                                                {{ ucfirst($request->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="small">{{ $request->created_at->format('Y-m-d') }}</span>
                                                        </td>
                                                        <td>
                                                            <button
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#requestModal"
                                                                class="btn btn-sm btn-outline-info px-2"
                                                                data-status="{{ ucfirst($request->status) }}"
                                                                data-requester="{{ $request->requester->name ?? '-' }}"
                                                                data-provider="{{ $request->provider->name ?? '-' }}"
                                                                data-requesteditem="{{ $request->requestedItem->name ?? '-' }}"
                                                                data-provideritem="{{ $request->providerItem->name ?? '-' }}"
                                                                data-is-asset="{{ $isAsset ? 1 : 0 }}"
                                                                data-quantity="{{ $request->quantity ?? '-' }}"
                                                                data-date="{{ $isAsset ? ($request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d') : '-') : ($request->preferred_date ? \Carbon\Carbon::parse($request->preferred_date)->format('Y-m-d') : '-') }}"
                                                                data-reserved-start="{{ $request->reserved_start ? \Carbon\Carbon::parse($request->reserved_start)->format('Y-m-d H:i') : '-' }}"
                                                                data-reserved-end="{{ $request->reserved_end ? \Carbon\Carbon::parse($request->reserved_end)->format('Y-m-d H:i') : '-' }}"
                                                                data-method="{{ $request->delivery_method ?? '-' }}"
                                                                data-contactname="{{ $request->contact_name ?? '-' }}"
                                                                data-contactemail="{{ $request->contact_email ?? '-' }}"
                                                                data-contactmobile="{{ $request->contact_mobile ?? '-' }}"
                                                                data-notes="{{ $request->notes ?? '-' }}"
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

        {{-- Details Modal --}}
        <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content glass shadow-lg border-0">
                    <div class="modal-header bg-primary text-white rounded-top-4">
                        <h5 class="modal-title fw-semibold" id="requestModalLabel">
                            <i class="bi bi-info-circle"></i> Resource Request Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-gradient-dark text-white">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-2"><span class="fw-semibold text-muted">Status:</span> <span id="modal-status" class="badge bg-warning text-dark"></span></div>
                                <div class="mb-2"><span class="fw-semibold text-muted">Requester:</span> <span id="modal-requester"></span></div>
                                <div class="mb-2"><span class="fw-semibold text-muted">Provider:</span> <span id="modal-provider"></span></div>
                                <div class="mb-2"><span class="fw-semibold text-muted">Item Needed:</span> <span id="modal-item"></span></div>
                                <div class="mb-2"><span class="fw-semibold text-muted">Provider's Item:</span> <span id="modal-provideritem"></span></div>
                            </div>
                            <div class="col-md-6">
                                <div id="modal-consumable-details" class="mb-3">
                                    <div><span class="fw-semibold text-muted">Quantity:</span> <span id="modal-quantity"></span></div>
                                    <div><span class="fw-semibold text-muted">Preferred Date:</span> <span id="modal-date"></span></div>
                                </div>
                                <div id="modal-asset-details" class="mb-3" style="display:none;">
                                    <div><span class="fw-semibold text-muted">Reserved Start:</span> <span id="modal-reserved-start"></span></div>
                                    <div><span class="fw-semibold text-muted">Reserved End:</span> <span id="modal-reserved-end"></span></div>
                                    <div><span class="fw-semibold text-muted">Preferred Date:</span> <span id="modal-date"></span></div>
                                </div>
                                <div><span class="fw-semibold text-muted">Delivery Method:</span> <span id="modal-method"></span></div>
                                <div><span class="fw-semibold text-muted">Contact Name:</span> <span id="modal-contactname"></span></div>
                                <div><span class="fw-semibold text-muted">Contact Email:</span> <span id="modal-contactemail"></span></div>
                                <div><span class="fw-semibold text-muted">Contact Mobile:</span> <span id="modal-contactmobile"></span></div>
                            </div>
                            <div class="col-12">
                                <div class="mt-2"><span class="fw-semibold text-muted">Notes:</span> <span id="modal-notes"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-gradient-dark rounded-bottom-4">
                        <button type="button" class="btn btn-outline-light px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Approve Modal and Receive Modal (as in your code) --}}
                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" id="approveForm">
                                @csrf
                                @method('PATCH')
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="approveModalLabel">Approve Resource Request</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to approve this request?</p>
                                        <div id="assetDetails" style="display: none;">
                                            <div class="alert alert-info">This is a bookable asset. Please review the reservation dates.</div>
                                            <div class="mb-2">
                                                <label class="form-label">Reserved Start</label>
                                                <input type="date" class="form-control" name="reserved_start" id="approveReservedStart">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Reserved End</label>
                                                <input type="date" class="form-control" name="reserved_end" id="approveReservedEnd">
                                            </div>
                                        </div>
                                        <div>
                                            <strong>Provider's Item:</strong> <span id="approveProviderItem"></span><br>
                                            <strong>Requested For:</strong> <span id="approveRequestedItem"></span><br>
                                            <strong>Quantity:</strong> <span id="approveQuantity"></span>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Approve</button>
                                    </div>
                                </div>
                            </form>
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
                    <!-- Asset (category needed) -->
                    <div id="asset-category-section" style="display:none;">
                        <p>
                            Please select a category to assign this received item in your inventory:<br>
                        </p>
                        <div class="mb-3">
                            <label for="categorySelect" class="form-label">Item Category <span class="text-danger">*</span></label>
                            <select name="inventory_category_id" id="categorySelect" class="form-select">
                                <option value="">-- Select Category --</option>
                                @foreach($myCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('inventory_category_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <!-- Consumable (no category) -->
                    <div id="consumable-section" style="display:none;">
                        <p>
                            Confirm receipt of this consumable.<br>
                            <span class="text-info">No category selection is needed for consumables.</span>
                        </p>
                    </div>
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
</div>

<style>
    body {
        background: linear-gradient(135deg, #2b2c3b 0%, #13131a 100%);
    }
    .glass {
        background: rgba(30,30,38,0.93) !important;
        border-radius: 2rem;
        backdrop-filter: blur(3px) saturate(120%);
    }
    .bg-gradient-dark {
        background: linear-gradient(135deg, #232338 0%, #202034 100%) !important;
    }
    .rounded-4 { border-radius: 1.5rem !important; }

    /* Make all text in the request details modal white */
    #requestModal .modal-content,
    #requestModal .modal-content * {
        color: #fff !important;
    }
</style>


<script>

    // Receive modal
document.addEventListener('DOMContentLoaded', function () {
    var receiveModal = document.getElementById('receiveModal');
    var receiveForm = document.getElementById('receiveForm');
    var assetSection = document.getElementById('asset-category-section');
    var consumableSection = document.getElementById('consumable-section');
    var categorySelect = document.getElementById('categorySelect');

    if (receiveModal) {
        receiveModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-request-id');
            var isAsset = button.getAttribute('data-is-asset') == '1' ? true : false;

            // Update form action
            if (receiveForm) {
                receiveForm.action = '/funeral/resource-requests/' + requestId + '/fulfill';
            }

            // Show asset section if asset, else show consumable section
            if (isAsset) {
                assetSection.style.display = '';
                consumableSection.style.display = 'none';
                if (categorySelect) categorySelect.setAttribute('required', 'required');
            } else {
                assetSection.style.display = 'none';
                consumableSection.style.display = '';
                if (categorySelect) categorySelect.removeAttribute('required');
            }
        });
    }
});

    // Approve modal with asset details
    document.addEventListener('DOMContentLoaded', function () {
        const approveModal = document.getElementById('approveModal');
        approveModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-request-id');
            const isAsset = button.getAttribute('data-is-asset');
            const reservedStart = button.getAttribute('data-reserved-start');
            const reservedEnd = button.getAttribute('data-reserved-end');
            const providerItem = button.getAttribute('data-provideritem');
            const requestedItem = button.getAttribute('data-requesteditem');
            const quantity = button.getAttribute('data-quantity');
            const form = document.getElementById('approveForm');

            form.action = '/funeral/resource-requests/' + requestId + '/approve';
            document.getElementById('approveProviderItem').textContent = providerItem;
            document.getElementById('approveRequestedItem').textContent = requestedItem;
            document.getElementById('approveQuantity').textContent = quantity;

            // Show/hide asset reservation fields
            if (parseInt(isAsset)) {
                document.getElementById('assetDetails').style.display = '';
                document.getElementById('approveReservedStart').value = reservedStart || '';
                document.getElementById('approveReservedEnd').value = reservedEnd || '';
            } else {
                document.getElementById('assetDetails').style.display = 'none';
                document.getElementById('approveReservedStart').value = '';
                document.getElementById('approveReservedEnd').value = '';
            }
        });
    });


document.addEventListener('DOMContentLoaded', function () {
    const requestModal = document.getElementById('requestModal');
    if (requestModal) {
        requestModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const isAsset = parseInt(button.getAttribute('data-is-asset'));

            // Set all common fields
            document.getElementById('modal-status').textContent = button.getAttribute('data-status');
            document.getElementById('modal-requester').textContent = button.getAttribute('data-requester');
            document.getElementById('modal-provider').textContent = button.getAttribute('data-provider');
            document.getElementById('modal-item').textContent = button.getAttribute('data-requesteditem');
            document.getElementById('modal-provideritem').textContent = button.getAttribute('data-provideritem');
            document.getElementById('modal-method').textContent = button.getAttribute('data-method');
            document.getElementById('modal-contactname').textContent = button.getAttribute('data-contactname');
            document.getElementById('modal-contactemail').textContent = button.getAttribute('data-contactemail');
            document.getElementById('modal-contactmobile').textContent = button.getAttribute('data-contactmobile');
            document.getElementById('modal-notes').textContent = button.getAttribute('data-notes');

            if (isAsset) {
                document.getElementById('modal-asset-details').style.display = '';
                document.getElementById('modal-consumable-details').style.display = 'none';
                document.getElementById('modal-reserved-start').textContent = button.getAttribute('data-reserved-start');
                document.getElementById('modal-reserved-end').textContent = button.getAttribute('data-reserved-end');
                document.getElementById('modal-date').textContent = button.getAttribute('data-reserved-start') || '-';
            } else {
                document.getElementById('modal-asset-details').style.display = 'none';
                document.getElementById('modal-consumable-details').style.display = '';
                document.getElementById('modal-quantity').textContent = button.getAttribute('data-quantity');
                document.getElementById('modal-date').textContent = button.getAttribute('data-date') || '-';
            }
        });
    }
});
</script>
</x-layouts.funeral>
