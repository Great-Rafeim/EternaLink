{{-- resources/views/notifications/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create New User')

@section('content')
    <div class="container py-4">
        <h2 class="mb-4">Notifications</h2>
        <ul class="list-group">
            @forelse($notifications as $notification)
                <li class="list-group-item d-flex flex-column">
                    <div>
                        {!! $notification->data['message'] ?? 'Notification' !!}
                    </div>
                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    <div class="mt-2">
                        @if(empty($notification->read_at))
                            <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary">Mark as read</button>
                            </form>
                        @endif
                        <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        @if(!empty($notification->data['url']))
                            <a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-link">View Details</a>
                        @endif
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted">No notifications found.</li>
            @endforelse
        </ul>
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
