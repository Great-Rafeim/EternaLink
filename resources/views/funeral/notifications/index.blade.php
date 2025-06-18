{{-- resources/views/funeral/notifications/index.blade.php --}}

<x-layouts.funeral>



    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0" style="color: #1565c0;">
                <i class="bi bi-bell-fill me-2"></i> Notifications
            </h2>
            <div>
                <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-success btn-sm" type="submit" @if($notifications->whereNull('read_at')->count() === 0) disabled @endif>
                        <i class="bi bi-check2-all me-1"></i> Mark all as read
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow rounded-4 border-0">
            <div class="card-body p-0">
                @if($notifications->count())
                    <ul class="list-group list-group-flush">
                        @foreach($notifications as $notification)
                            <li class="list-group-item d-flex justify-content-between align-items-start @if(is_null($notification->read_at)) bg-light @endif">
                                <div>
                                    <div class="fw-semibold" style="font-size: 1.08rem;">
                                        {!! $notification->data['message'] ?? 'Notification' !!}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="ms-auto d-flex align-items-center gap-2">
                                    @if(is_null($notification->read_at))
                                        <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-link btn-sm text-success" type="submit" title="Mark as read">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger" type="submit" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @if(!empty($notification->data['url']))
                                        <a href="{{ route('notifications.redirect', $notification->id) }}" class="btn btn-link btn-sm text-primary" title="View details">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4 px-4">
                        {{ $notifications->links('pagination::bootstrap-5') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash display-3 text-muted mb-3"></i>
                        <h4 class="text-muted">No notifications</h4>
                        <p class="text-secondary">You have no notifications at this time.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.funeral>
