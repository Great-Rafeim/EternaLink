@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    {{-- Welcome Header --}}
    <div class="mb-4">
        <h2 class="fw-bold">Welcome back, {{ Auth::user()->name }} 👋</h2>
        <p class="text-muted">Here's an overview of your system.</p>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total Users</h6>
                            <h4 class="fw-bold">{{ $totalUsers }}</h4>
                        </div>
                        <i class="bi bi-people fs-2 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Funeral</h6>
                            <h4 class="fw-bold">{{ $funeralCount }}</h4>
                        </div>
                        <i class="bi bi-truck fs-2 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Cemetery</h6>
                            <h4 class="fw-bold">{{ $cemeteryCount }}</h4>
                        </div>
                        <i class="bi bi-tree fs-2 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-dark shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Recent Logins</h6>
                            <h4 class="fw-bold">{{ $logins->count() }}</h4>
                        </div>
                        <i class="bi bi-clock-history fs-2 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Login History Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Recent Login History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Login Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logins as $login)
                            <tr>
                                <td>{{ $login->user->name }}</td>
                                <td>{{ $login->user->email }}</td>
                                <td class="text-capitalize">{{ $login->user->role }}</td>
                                <td>{{ $login->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No login history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
