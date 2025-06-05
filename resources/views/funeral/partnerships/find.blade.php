<x-layouts.funeral>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('funeral.partnerships.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Partnerships
            </a>
            <h2 class="text-white mb-0">Find Partners</h2>
            <div></div> {{-- For alignment --}}
        </div>

        <!-- Search form -->
        <form method="GET" action="{{ route('funeral.partnerships.find') }}" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search funeral parlors by name" value="{{ $search ?? '' }}">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>

        <!-- Flash messages -->
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Potential partners list -->
        <div class="card bg-dark border-0 shadow mb-2">
            <div class="card-header bg-primary text-white fw-semibold">
                Available Funeral Parlors
            </div>
            <div class="card-body p-0">
                @if($potentialPartners->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-emoji-frown fs-4"></i>
                        <div>No available partners found.</div>
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($potentialPartners as $partner)
                            <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center px-3 py-3">
                                <span class="fw-semibold">{{ $partner->name }}</span>
                                <form method="POST" action="{{ route('funeral.partnerships.request') }}">
                                    @csrf
                                    <input type="hidden" name="partner_id" value="{{ $partner->id }}">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-person-plus"></i> Send Request
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-3">
                        {{ $potentialPartners->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.funeral>
