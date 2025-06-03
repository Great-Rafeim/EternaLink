@extends('layouts.admin')

@section('title', 'Create New User')

@section('content')
<div class="container">
    <h1>Create New User</h1>

    {{-- Back Button --}}
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mb-3">← Back to User List</a>

    {{-- Create User Form --}}
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" name="name" id="name" class="form-control" required value="{{ old('name') }}">
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" required value="{{ old('email') }}">
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="role" class="form-label">User Role</label>
            <select name="role" id="role" class="form-control" required>
                <option value="">Select role</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="client" {{ old('role') === 'client' ? 'selected' : '' }}>Client</option>
                <option value="funeral" {{ old('role') === 'funeral' ? 'selected' : '' }}>Funeral Parlor</option>
                <option value="cemetery" {{ old('role') === 'cemetery' ? 'selected' : '' }}>Cemetery</option>
            </select>
            @error('role') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Initial Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
            @error('password') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Create User</button>
    </form>
</div>
@endsection
