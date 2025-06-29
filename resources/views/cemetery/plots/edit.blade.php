<x-cemetery-layout>
    <div class="container" style="max-width:540px;">

        {{-- Responsive Navigation Bar --}}
        <nav class="my-4">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-7 d-flex flex-wrap gap-2 mb-2 mb-md-0">
                    <a href="{{ route('cemetery.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left-circle"></i> Dashboard
                    </a>
                    <a href="{{ route('cemetery.plots.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-grid-3x3-gap"></i> All Plots
                    </a>
                </div>
                <div class="col-12 col-md-5 d-flex flex-wrap justify-content-md-end gap-2">
                    @if($plot->status === 'occupied' && $plot->occupation)
                        <a href="{{ route('cemetery.plots.occupations.edit', [$plot, $plot->occupation]) }}" class="btn btn-secondary">
                            <i class="bi bi-person-fill-check"></i> Edit Occupation
                        </a>
                        <form action="{{ route('cemetery.plots.occupations.destroy', [$plot, $plot->occupation]) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Remove occupation from this plot?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i> Remove Occupation
                            </button>
                        </form>
                    @else
                        <a href="{{ route('cemetery.plots.occupations.create', $plot) }}" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Assign Occupation
                        </a>
                    @endif
                </div>
            </div>
        </nav>
@if($plot->status === 'occupied' && $plot->occupation)
    <div class="alert bg-secondary bg-opacity-10 border-0 mb-4 shadow-sm p-3">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <i class="bi bi-person-fill-check text-success fs-2"></i>
            <div>
                <div class="fw-bold fs-5 text-success mb-1">
                    {{ trim($plot->occupation->deceased_first_name . ' ' . $plot->occupation->deceased_middle_name . ' ' . $plot->occupation->deceased_last_name) }}
                </div>
                <div class="text-secondary small">
                    <span class="me-3">
                        <i class="bi bi-cake2"></i>
                        Born: 
                        {{ $plot->occupation->deceased_birthday 
                            ? \Carbon\Carbon::parse($plot->occupation->deceased_birthday)->format('M d, Y')
                            : 'N/A' }}
                    </span>
                    <span>
                        <i class="bi bi-x-octagon"></i>
                        Died: 
                        {{ $plot->occupation->deceased_date_of_death
                            ? \Carbon\Carbon::parse($plot->occupation->deceased_date_of_death)->format('M d, Y')
                            : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endif
@if($plot->status === 'reserved' && isset($reservedDetail) && $reservedDetail)
    <div class="alert bg-warning bg-opacity-10 border-0 mb-4 shadow-sm p-3">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <i class="bi bi-bookmark-star text-warning fs-2"></i>
            <div>
                <div class="fw-bold fs-5 text-warning mb-1">
                    Reserved For: 
                    {{ trim(
                        $reservedDetail->deceased_first_name . ' ' .
                        $reservedDetail->deceased_middle_name . ' ' .
                        $reservedDetail->deceased_last_name
                    ) }}
                </div>
                <div class="text-secondary small">
                    @if($reservedDetail->deceased_birthday)
                        <i class="bi bi-cake2"></i>
                        Born: {{ \Carbon\Carbon::parse($reservedDetail->deceased_birthday)->format('M d, Y') }}
                    @endif
                    @if($reservedDetail->deceased_date_of_death)
                        <span class="ms-3">
                            <i class="bi bi-x-octagon"></i>
                            Died: {{ \Carbon\Carbon::parse($reservedDetail->deceased_date_of_death)->format('M d, Y') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

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
