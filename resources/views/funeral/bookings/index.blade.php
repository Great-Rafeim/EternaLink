{{-- resources/views/funeral/bookings/index.blade.php --}}

<x-layouts.funeral>
    <style>
        .booking-status-badge {
            min-width: 95px;
            display: inline-flex;
            align-items: center;
            gap: 0.25em;
            font-size: 0.93em;
            font-weight: 500;
        }
        .table th, .table td {
            vertical-align: middle !important;
            font-size: 0.97em;
        }
        .table thead th {
            background: #f7f9fc !important;
            font-size: 0.98em;
            font-weight: 600;
            color: #395269;
            border-bottom: 2px solid #e7eaf3 !important;
        }
        .search-bar input[type="search"], .search-bar input[type="text"] {
            max-width: 180px;
            border-radius: 0.3rem !important;
            font-size: 0.97em;
        }
        .search-bar select {
            font-size: 0.97em;
            border-radius: 0.3rem !important;
        }
        .search-bar .input-group-text {
            background: #f4f7fb !important;
            border-right: 0;
            color: #395269 !important;
        }
        .search-bar .form-label {
            font-weight: 500;
            font-size: 0.95em;
        }
        .form-select, .form-control { box-shadow: none !important; }
        .filter-row .form-control, .filter-row .form-select { height: 2.15em; }
        .card { border-radius: 1.1rem !important; }
        .table-responsive { border-radius: 0.85rem; }
        .card-header { border-radius: 1.1rem 1.1rem 0 0 !important; }
        .pagination { margin-bottom: 0; }
    </style>

    <div class="container py-4">
        <div class="d-flex align-items-center mb-3">
            <i class="bi bi-list-task fs-3 text-primary me-2"></i>
            <h2 class="fw-bold mb-0 text-primary">Booking Management</h2>
        </div>

        {{-- FILTER BAR --}}
        <form id="filterForm" method="GET" action="{{ route('funeral.bookings.index') }}" class="card mb-3 shadow-sm border-0 px-3 py-2 search-bar" autocomplete="off">
            <div class="row filter-row g-2 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="search" class="form-label mb-1">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            class="form-control border-start-0"
                            placeholder="Client, package, agent..."
                            autocomplete="off"
                        >
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        @foreach($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected(request('status', 'all') == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="sort" class="form-label mb-1">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="created_at" @selected(request('sort', 'created_at') == 'created_at')>Created Date</option>
                        <option value="updated_at" @selected(request('sort') == 'updated_at')>Last Updated</option>
                        <option value="client" @selected(request('sort') == 'client')>Client Name</option>
                        <option value="status" @selected(request('sort') == 'status')>Status</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 col-lg-1">
                    <label for="dir" class="form-label mb-1">Order</label>
                    <select name="dir" id="dir" class="form-select">
                        <option value="desc" @selected(request('dir', 'desc') == 'desc')>↓</option>
                        <option value="asc" @selected(request('dir') == 'asc')>↑</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 col-lg-2 text-end">
                    <button type="submit" class="btn btn-primary fw-semibold px-3 me-2 d-none" id="filterSubmit">
                        <i class="bi bi-funnel"></i>
                        <span class="d-none d-md-inline">Filter</span>
                    </button>
                    <a href="{{ route('funeral.bookings.index') }}" class="btn btn-link text-muted border-0 px-2">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>

        {{-- AJAX BOOKINGS TABLE WRAPPER (UNIQUE ID) --}}
        <div id="ajax-bookings-table">
            {{-- TABLE: ALL BOOKINGS --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-primary text-white d-flex align-items-center rounded-top-3 py-2">
                    <i class="bi bi-calendar2-check fs-4 me-2"></i>
                    <div>
                        <div class="fw-bold fs-6">All Bookings</div>
                        <div class="small text-white-50">Active, declined, completed and all other statuses.</div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Package</th>
                                    <th>Status</th>
                                    <th>Agent</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($bookings as $booking)
                                @php
                                    $statusMap = [
                                        'pending'          => ['warning',  'hourglass-split'],
                                        'confirmed'        => ['info',     'check-circle'],
                                        'for_payment_details' => ['primary','wallet'],
                                        'in_progress'      => ['secondary','pencil-square'],
                                        'for_initial_review' => ['warning','journal-check'],
                                        'for_final_review' => ['warning','file-earmark-check'],
                                        'for_review'       => ['secondary','journal-check'],
                                        'approved'         => ['success',  'hand-thumbs-up'],
                                        'ongoing'          => ['primary',  'arrow-repeat'],
                                        'completed'        => ['success',  'award'],
                                        'declined'         => ['danger',   'x-circle'],
                                        'cancelled'        => ['danger',   'slash-circle'],
                                    ];
                                    $s = $statusMap[$booking->status] ?? ['secondary','question'];
                                    $assignedAgent = $booking->bookingAgent->agentUser ?? null;
                                    $isDeclined    = $booking->status === 'declined';
                                    $isCompleted   = $booking->status === 'completed';
                                @endphp
                                <tr class="{{ $isDeclined ? 'table-danger' : ($isCompleted ? 'table-success' : '') }}">
                                    <td class="fw-bold text-primary small">
                                        #{{ $booking->id }}
                                    </td>
                                    <td>
                                        <i class="bi bi-person-circle me-1"></i>
                                        {{ $booking->client->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <i class="bi bi-box2-heart me-1"></i>
                                        {{ $booking->package->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="badge booking-status-badge bg-{{ $s[0] }}-subtle text-{{ $s[0] }}">
                                            <i class="bi bi-{{ $s[1] }}"></i>
                                            {{ \Illuminate\Support\Str::headline($booking->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-badge me-1"></i>
                                        {{ $assignedAgent ? $assignedAgent->name : 'Unassigned' }}
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ $booking->created_at->format('M d, Y') }}
                                    </td>
                                    <td>
                                        <i class="bi bi-clock-history me-1"></i>
                                        {{ $booking->updated_at->format('M d, Y') }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('funeral.bookings.show', $booking->id) }}"
                                           class="btn btn-outline-primary btn-sm rounded-pill px-2">
                                            <i class="bi bi-eye"></i>
                                            <span class="visually-hidden">View</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-emoji-frown fs-4 d-block mb-2"></i>
                                        No bookings found for your criteria.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($bookings instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="card-footer py-2 bg-light d-flex justify-content-end">
                    {{ $bookings->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>

            {{-- CUSTOMIZATION REQUESTS --}}
            @if(isset($customizationRequests) && $customizationRequests->count())
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-danger bg-opacity-10 d-flex align-items-center py-2">
                        <i class="bi bi-sliders fs-4 me-2 text-danger"></i>
                        <div>
                            <span class="fw-bold fs-6 text-danger">Customization Requests</span>
                            <div class="small text-muted">Pending customization requests for your review.</div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Client</th>
                                        <th>Package</th>
                                        <th>Status</th>
                                        <th>Requested On</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($customizationRequests as $booking)
                                    @php
                                        $pendingCustomization = $booking->customizationRequests->first();
                                    @endphp
                                    @if($pendingCustomization)
                                        <tr>
                                            <td class="fw-bold text-danger small">
                                                #{{ $booking->id }}
                                            </td>
                                            <td>
                                                <i class="bi bi-person-circle me-1"></i>
                                                {{ $booking->client->name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <i class="bi bi-box2-heart me-1"></i>
                                                {{ $booking->package->name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-warning text-dark booking-status-badge">
                                                    <i class="bi bi-sliders"></i>
                                                    {{ ucfirst($pendingCustomization->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <i class="bi bi-calendar-event me-1"></i>
                                                {{ $pendingCustomization->updated_at->format('M d, Y') }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('funeral.bookings.customization.show', [$booking->id, $pendingCustomization->id]) }}"
                                                   class="btn btn-danger btn-sm rounded-pill px-2">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span class="visually-hidden">Review Customization</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div> {{-- /#ajax-bookings-table --}}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('filterForm');
            const ajaxTable = document.getElementById('ajax-bookings-table');
            let lastRequestUrl = filterForm.getAttribute('action');

            function ajaxifyPaginationLinks() {
                ajaxTable.querySelectorAll('.pagination a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        fetchAndUpdate(link.href);
                    });
                });
            }

            function fetchAndUpdate(url = null) {
                url = url || lastRequestUrl;
                lastRequestUrl = url.split('?')[0];
                const params = new URLSearchParams(new FormData(filterForm)).toString();
                fetch(url + (url.includes('?') ? '&' : '?') + params, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.getElementById('ajax-bookings-table');
                    if (newTable) {
                        ajaxTable.innerHTML = newTable.innerHTML;
                        ajaxifyPaginationLinks();
                    }
                });
            }

            filterForm.querySelectorAll('select, input').forEach(el => {
                el.addEventListener('change', () => fetchAndUpdate());
                if (el.type === 'text') {
                    el.addEventListener('keyup', function(e) {
                        if (el.value.length >= 3 || el.value.length === 0 || e.key === 'Enter') fetchAndUpdate();
                    });
                }
            });
            filterForm.addEventListener('submit', function(e){
                e.preventDefault();
                fetchAndUpdate();
            });
            ajaxifyPaginationLinks();
        });
    </script>

</x-layouts.funeral>
