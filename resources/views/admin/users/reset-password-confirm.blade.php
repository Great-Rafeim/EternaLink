@extends('layouts.admin')

@section('content')
    <h1>Reset Password for {{ $user->name }}</h1>
    <p>Are you sure you want to reset this user's password to a default or send a reset link?</p>

    <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">Confirm Reset Password</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@endsection
