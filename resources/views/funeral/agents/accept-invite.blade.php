<x-guest-layout>
    <h2 class="mb-3 text-center fw-bold" style="color:#ffc107;">Agent Invitation</h2>
    <div class="mb-4 text-center text-muted">
        Complete your agent registration to join the funeral parlor team.
    </div>

    <form method="POST" action="{{ request()->fullUrl() }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ $email }}" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-warning w-100 py-2 fw-semibold">Complete Registration</button>
    </form>
    <div class="mt-3 text-center">
        <a href="{{ route('login') }}" class="guest-link">
            <i class="bi bi-arrow-left"></i> Back to Login
        </a>
    </div>
</x-guest-layout>
