<x-cemetery-layout>
    <div class="container" style="max-width:540px;">
        <div class="card bg-dark border-0 shadow-lg my-5">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <strong>There were some issues with your input:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('cemetery.plots.update', $plot) }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    {{-- Header row: Title left, Status selector right --}}
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                        <h1 class="h4 fw-bold mb-0 text-white">
                            Edit Plot #{{ $plot->plot_number }}
                        </h1>
                        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0" style="min-width:220px;">
                            <label for="status" class="form-label mb-0 me-2 text-white fw-normal">Status</label>
                            <select name="status" id="status"
                                class="form-select form-select-sm text-center
                                {{ old('status', $plot->status) == 'available' ? 'text-success fw-bold'
                                  : (old('status', $plot->status) == 'reserved' ? 'text-warning fw-bold'
                                  : 'text-danger fw-bold') }}"
                                style="width:130px;">
                                <option value="available"
                                    class="text-success fw-bold"
                                    {{ old('status', $plot->status) == 'available' ? 'selected' : '' }}>
                                    Available
                                </option>
                                <option value="reserved"
                                    class="text-warning fw-bold"
                                    {{ old('status', $plot->status) == 'reserved' ? 'selected' : '' }}>
                                    Reserved
                                </option>
                                <option value="occupied"
                                    class="text-danger fw-bold"
                                    {{ old('status', $plot->status) == 'occupied' ? 'selected' : '' }}>
                                    Occupied
                                </option>
                            </select>
                        </div>
                    </div>

                    {{-- Regular plot fields (excluding status) --}}
                    @include('cemetery.plots.forms.first', ['plot' => $plot])

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-cemetery-layout>
