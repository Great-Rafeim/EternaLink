<x-client-layout>
    <div class="container px-0 px-md-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="color:#1565c0;">Funeral Parlors</h2>

                <div class="mb-4">
                    <form method="GET" action="{{ route('client.parlors.index') }}" class="row g-2 align-items-center">
                        {{-- Name Search --}}
                        <div class="col-12 col-md-8 col-lg-7">
                            <input type="text"
                                   name="q"
                                   value="{{ request('q') }}"
                                   class="form-control form-control-lg"
                                   placeholder="Search by funeral parlor name...">
                        </div>
                        {{-- Toggle Address Search --}}
                        <div class="col-auto">
                            <button
                                type="button"
                                class="btn btn-outline-secondary rounded-pill px-3"
                                id="toggle-address-search"
                                onclick="toggleAddressSearch()"
                                >
                                <i class="bi bi-geo-alt me-1"></i>
                                Search by address
                            </button>
                        </div>
                        {{-- Search Button --}}
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                        {{-- Clear --}}
                        @if(request('q') || request('address'))
                        <div class="col-auto">
                            <a href="{{ route('client.parlors.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                                <i class="bi bi-x-lg"></i> Clear
                            </a>
                        </div>
                        @endif

                        {{-- Address Search, hidden by default --}}
                        <div class="col-12 mt-2" id="address-search-bar" style="{{ request('address') ? '' : 'display:none;' }}">
                            <input type="text"
                                   name="address"
                                   value="{{ request('address') }}"
                                   class="form-control form-control-lg"
                                   placeholder="Search by address...">
                        </div>
                    </form>
                </div>


                <p class="text-secondary mb-0">Browse available funeral parlors in the EternaLink system.</p>
            </div>
        </div>
        <div class="row g-4">
            @forelse($parlors as $parlor)
                <div class="col-12 col-md-6 col-lg-4 d-flex">
                    <div class="card shadow-sm border-0 rounded-4 flex-fill">
                        @if($parlor->funeralParlor && $parlor->funeralParlor->image)
                            <img src="{{ asset('storage/'.$parlor->funeralParlor->image) }}" class="card-img-top rounded-top-4" style="height:170px; object-fit:cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded-top-4" style="height:170px;">
                                <i class="bi bi-building" style="font-size: 2.2rem; color:#aaa;"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <h5 class="card-title mb-1 fw-semibold">{{ $parlor->name }}</h5>
                            <div class="small text-muted mb-2">
                                {{-- Address from funeral_parlor, or fallback --}}
                                {{ $parlor->funeralParlor->address ?? 'No address provided' }}
                            </div>
                            <div class="mb-3 text-secondary small" style="min-height:2.5em;">
                                {{ Str::limit($parlor->funeralParlor->description ?? '', 80) }}
                            </div>
                            <a href="{{ route('client.parlors.service_packages', $parlor->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                                <i class="bi bi-eye me-1"></i> Show Services Packages
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No funeral parlors found.
                    </div>
                </div>
            @endforelse
        </div>
        <div class="d-flex justify-content-center mt-5">
            {{ $parlors->links() }}
        </div>
    </div>

    <script>
        function toggleAddressSearch() {
            var bar = document.getElementById('address-search-bar');
            bar.style.display = (bar.style.display === 'none' || bar.style.display === '') ? 'block' : 'none';
        }
        // Auto-show if previously searched
        document.addEventListener('DOMContentLoaded', function() {
            @if(request('address'))
                document.getElementById('address-search-bar').style.display = 'block';
            @endif
        });
    </script>
    
</x-client-layout>
