{{-- resources/views/agent/dashboard.blade.php --}}
<x-agent-layout title="Agent Dashboard">
    <div class="container">
        <div class="row align-items-center mb-4 g-3">
            <div class="col-md">
                <h1 class="fw-bold display-5 mb-1 text-primary">Welcome, {{ Auth::user()->name }} 👋</h1>
                <div class="text-muted">Here you can manage and process the bookings assigned to you.</div>
            </div>
            <div class="col-md-auto">
                <div class="card border-0 shadow-sm d-flex flex-row align-items-center p-3" style="min-width:170px;">
                    <i class="bi bi-calendar-check text-primary display-6 me-3"></i>
                    <div>
                        <div class="h4 fw-bold text-primary mb-0">{{ $bookings->count() }}</div>
                        <div class="small text-muted">Active Bookings</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-light d-flex align-items-center justify-content-between rounded-top-4">
                <div class="fw-semibold fs-5 text-primary d-flex align-items-center">
                    <i class="bi bi-journal-text me-2 fs-4"></i> Assigned Bookings
                </div>
                {{-- Future search/filter placeholder --}}
                <!--
                <form class="d-none d-md-block">
                    <input type="search" class="form-control form-control-sm" placeholder="Search bookings...">
                </form>
                -->
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Client</th>
                            <th>Funeral Home</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Assigned At</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-person text-primary"></i>
                                        <span>{{ $booking->client->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="small text-muted">{{ $booking->client->email ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-buildings text-primary"></i>
                                        <span>{{ $booking->funeralHome->name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $booking->package->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @php $label = $booking->statusLabel(); @endphp
                                    <span class="badge bg-{{ $label['color'] }}-subtle text-{{ $label['color'] }} px-2 py-2">
                                        <i class="bi bi-{{ $label['icon'] }} me-1"></i>
                                        {{ $label['label'] }}
                                    </span>
                                </td>
                                <td class="text-muted">
                                    {{ optional($booking->bookingAgent)->created_at ? $booking->bookingAgent->created_at->format('Y-m-d H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('agent.bookings.show', $booking) }}"
                                       class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 rounded-pill shadow-sm">
                                        <i class="bi bi-eye"></i> View / Handle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-5 text-center text-muted fs-5">
                                    <i class="bi bi-emoji-frown fs-3 me-2"></i>
                                    No assigned bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-agent-layout>
