<x-client-layout>
    <div class="container px-0 px-md-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="color:#222;">Cemeteries</h2>
                <div class="mb-4">
                    <form method="GET" action="{{ route('client.cemeteries.index') }}" class="row g-2 align-items-center">
                        <div class="col-12 col-md-8 col-lg-7">
                            <input type="text"
                                   name="q"
                                   value="{{ request('q') }}"
                                   class="form-control form-control-lg"
                                   placeholder="Search by cemetery name...">
                        </div>
                        <div class="col-auto">
                            <button type="button"
                                class="btn btn-outline-secondary rounded-pill px-3"
                                id="toggle-address-search"
                                onclick="toggleAddressSearch()">
                                <i class="bi bi-geo-alt me-1"></i>
                                Search by address
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                        @if(request('q') || request('address'))
                        <div class="col-auto">
                            <a href="{{ route('client.cemeteries.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                                <i class="bi bi-x-lg"></i> Clear
                            </a>
                        </div>
                        @endif
                        <div class="col-12 mt-2" id="address-search-bar" style="{{ request('address') ? '' : 'display:none;' }}">
                            <input type="text"
                                   name="address"
                                   value="{{ request('address') }}"
                                   class="form-control form-control-lg"
                                   placeholder="Search by address...">
                        </div>
                    </form>
                </div>
                <p class="text-secondary mb-0">Browse available cemeteries in the EternaLink system.</p>
            </div>
        </div>
        <div class="row g-4">
            @forelse($cemeteryUsers as $user)
                @php $cemetery = $user->cemetery; @endphp
                <div class="col-12 col-md-6 col-lg-4 d-flex">
                    <div class="card shadow-sm border-0 rounded-4 flex-fill">
                        @if($cemetery && $cemetery->image_path)
                            <img src="{{ asset('storage/'.$cemetery->image_path) }}" class="card-img-top rounded-top-4" style="height:170px; object-fit:cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded-top-4" style="height:170px;">
                                <i class="bi bi-building" style="font-size: 2.2rem; color:#aaa;"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <h5 class="card-title mb-1 fw-semibold">{{ $user->name }}</h5>
                            <div class="small text-muted mb-2">
                                {{ $cemetery->address ?? 'No address provided' }}
                            </div>
                            <div class="mb-3 text-secondary small" style="min-height:2.5em;">
                                {{ Str::limit($cemetery->description ?? '', 80) ?: 'No description provided.' }}
                            </div>
                            <!-- Button trigger modal -->
                            <button
                                class="btn btn-outline-primary btn-sm rounded-pill px-4"
                                data-bs-toggle="modal"
                                data-bs-target="#cemeteryModal-{{ $user->id }}">
                                <i class="bi bi-calendar-plus me-1"></i> Book or View
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal for this cemetery -->
                <div class="modal fade" id="cemeteryModal-{{ $user->id }}" tabindex="-1" aria-labelledby="cemeteryModalLabel-{{ $user->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4">
                      <div class="modal-header">
                        <h5 class="modal-title" id="cemeteryModalLabel-{{ $user->id }}">
                            {{ $user->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                          @if($cemetery && $cemetery->image_path)
                              <img src="{{ asset('storage/'.$cemetery->image_path) }}" class="rounded-3 mb-3 w-100" style="max-height: 220px; object-fit:cover;">
                          @endif
                          <div class="mb-2">
                              <strong>Address:</strong>
                              <div class="text-muted">{{ $cemetery->address ?? 'No address provided' }}</div>
                          </div>
                          @if($cemetery && $cemetery->contact_number)
                              <div class="mb-2">
                                  <strong>Contact:</strong>
                                  <div class="text-muted">{{ $cemetery->contact_number }}</div>
                              </div>
                          @endif
                          <div class="mb-2">
                              <strong>Description:</strong>
                              <div class="text-muted">{{ $cemetery->description ?? 'No description provided.' }}</div>
                          </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('client.cemeteries.booking', $user->id) }}" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-calendar-plus me-1"></i> Book Now
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No cemeteries found.
                    </div>
                </div>
            @endforelse
        </div>
        <div class="d-flex justify-content-center mt-5">
            {{ $cemeteryUsers->links() }}
        </div>
    </div>
    <script>
        function toggleAddressSearch() {
            var bar = document.getElementById('address-search-bar');
            bar.style.display = (bar.style.display === 'none' || bar.style.display === '') ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', function() {
            @if(request('address'))
                document.getElementById('address-search-bar').style.display = 'block';
            @endif
        });
    </script>
</x-client-layout>
