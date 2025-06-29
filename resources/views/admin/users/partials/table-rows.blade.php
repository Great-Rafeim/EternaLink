@forelse ($users as $user)
    <tr>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td class="text-capitalize">{{ $user->role }}</td>
        <td>
            @if($user->status === 'pending')
                <span class="badge bg-warning text-dark">Pending</span>
            @elseif($user->status === 'approved')
                <span class="badge bg-success">Approved</span>
            @elseif($user->status === 'rejected')
                <span class="badge bg-danger">Rejected</span>
            @else
                <span class="badge bg-light text-muted">N/A</span>
            @endif
        </td>
        <td>{{ $user->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
        <td>
            <div class="dropdown text-center">
                <button class="btn btn-link p-0" type="button" id="actionDropdown{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown{{ $user->id }}">
                    <li>
                        <a href="{{ route('admin.users.show', $user->id) }}" class="dropdown-item">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="dropdown-item">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    </li>
                    <li>
                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            @csrf
                            @method('DELETE')
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center">No users found.</td>
    </tr>
@endforelse
