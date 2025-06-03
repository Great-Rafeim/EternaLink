@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">User Management</h1>

    {{-- Role Filter Tabs --}}
    <ul class="nav nav-tabs mb-4">
        @php
            $roles = ['all' => 'All', 'admin' => 'Admin', 'client' => 'Client', 'funeral' => 'Funeral', 'cemetery' => 'Cemetery'];
        @endphp
        @foreach ($roles as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $role === $key ? 'active' : '' }}"
                   href="{{ $key === 'all' ? route('admin.users.index') : route('admin.users.index', ['role' => $key]) }}">
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Search Form --}}
    <form method="GET" action="{{ route('admin.users.index', ['role' => $role !== 'all' ? $role : null]) }}" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or email...">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Create and Export Buttons --}}
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.users.create') }}" class="btn btn-success">+ Create New User</a>
        <a href="{{ route('admin.users.export') }}" class="btn btn-outline-secondary">Export to CSV</a>
    </div>

    {{-- User Table --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody id="user-table-body">
                @include('admin.users.partials.table-rows', ['users' => $users])
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $users->withQueryString()->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('input[name="search"]').addEventListener('keyup', function () {
        const query = this.value;
        const role = '{{ $role }}';

        fetch(`/admin/users/ajax-search?search=${query}&role=${role}`)
            .then(response => response.text())
            .then(html => {
                document.querySelector('#user-table-body').innerHTML = html;
            });
    });
</script>
@endpush
