<x-layouts.funeral>
    <div class="container py-5" wire:poll.10s>
        <h1 class="h3 text-white mb-4">Notifications</h1>

        {{-- Unread Notifications --}}
        <div class="mb-5">
            <h2 class="h5 text-warning">Unread Notifications</h2>
            <ul class="list-group list-group-flush">
                @forelse ($unread as $notification)
                    <li class="list-group-item bg-dark text-light d-flex justify-content-between align-items-center">
                        <span>{!! $notification->data['message'] ?? 'Notification' !!}</span>
                        <form action="{{ route('funeral.notifications.read', $notification->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-outline-light">Mark as Read</button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item bg-dark text-muted">No unread notifications</li>
                @endforelse
            </ul>

            @if ($unread instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-3">
                    {{ $unread->links() }}
                </div>
            @endif
        </div>

        {{-- Read Notifications --}}
        <div>
            <h2 class="h5 text-secondary">Read Notifications</h2>
            <ul class="list-group list-group-flush">
                @forelse ($read as $notification)
                    <li class="list-group-item bg-secondary text-light">
                        {!! $notification->data['message'] ?? 'Notification' !!}
                        <small class="d-block text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </li>
                @empty
                    <li class="list-group-item bg-dark text-muted">No read notifications</li>
                @endforelse
            </ul>

            @if ($read instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-3">
                    {{ $read->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.funeral>
