{{-- resources/views/cemetery/plots/occupations/edit.blade.php --}}

<x-cemetery-layout>
    <div class="container" style="max-width: 600px;">

        {{-- Navigation Bar --}}
        <nav class="my-4">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-8 d-flex flex-wrap gap-2 mb-2 mb-md-0">
                    <a href="{{ route('cemetery.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left-circle"></i> Dashboard
                    </a>
                    <a href="{{ route('cemetery.plots.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-grid-3x3-gap"></i> All Plots
                    </a>
                    <a href="{{ route('cemetery.plots.edit', $plot) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil-square"></i> Edit Plot #{{ $plot->plot_number }}
                    </a>
                </div>
                <div class="col-12 col-md-4 d-flex flex-wrap justify-content-md-end gap-2">
                    <form action="{{ route('cemetery.plots.occupations.destroy', [$plot, $occupation]) }}"
                        method="POST" class="d-inline"
                        onsubmit="return confirm('Are you sure you want to unassign (remove) this occupation?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle"></i> Unassign Occupation
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <div class="card bg-dark border-0 shadow-lg my-5">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold text-white mb-4">
                    Edit Occupation for Plot #{{ $plot->plot_number }}
                </h1>

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

                @php
                    // Helper for field value precedence: old > autofill > occupation > ''
                    function pref($field, $autofill, $occupation) {
                        return old($field, $autofill[$field] ?? $occupation->$field ?? '');
                    }
                @endphp

                <form method="POST" action="{{ route('cemetery.plots.occupations.update', [$plot, $occupation]) }}" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label text-white">Deceased First Name <span class="text-danger">*</span></label>
                        <input type="text" name="deceased_first_name" required
                            value="{{ pref('deceased_first_name', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Deceased Middle Name</label>
                        <input type="text" name="deceased_middle_name"
                            value="{{ pref('deceased_middle_name', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Deceased Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="deceased_last_name" required
                            value="{{ pref('deceased_last_name', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Nickname</label>
                        <input type="text" name="deceased_nickname"
                            value="{{ pref('deceased_nickname', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" />
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white">Sex <span class="text-danger">*</span></label>
                            <select name="deceased_sex" class="form-select bg-dark text-white border-secondary" required>
                                <option value="">--</option>
                                <option value="Male" {{ in_array(strtolower(pref('deceased_sex', $autofill ?? [], $occupation)), ['male', 'm']) ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ in_array(strtolower(pref('deceased_sex', $autofill ?? [], $occupation)), ['female', 'f']) ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ pref('deceased_sex', $autofill ?? [], $occupation) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white">Birthday <span class="text-danger">*</span></label>
                            <input type="date" name="deceased_birthday"
                                value="{{ pref('deceased_birthday', $autofill ?? [], $occupation) }}"
                                class="form-control bg-dark text-white border-secondary" required />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white">Date of Death <span class="text-danger">*</span></label>
                            <input type="date" name="deceased_date_of_death"
                                value="{{ pref('deceased_date_of_death', $autofill ?? [], $occupation) }}"
                                class="form-control bg-dark text-white border-secondary" required />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-white">Age <span class="text-danger">*</span></label>
                            <input type="number" name="deceased_age"
                                value="{{ pref('deceased_age', $autofill ?? [], $occupation) }}"
                                class="form-control bg-dark text-white border-secondary" min="0" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Civil Status <span class="text-danger">*</span></label>
                        <input type="text" name="deceased_civil_status"
                            value="{{ pref('deceased_civil_status', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Residence <span class="text-danger">*</span></label>
                        <input type="text" name="deceased_residence"
                            value="{{ pref('deceased_residence', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Citizenship <span class="text-danger">*</span></label>
                        <input type="text" name="deceased_citizenship"
                            value="{{ pref('deceased_citizenship', $autofill ?? [], $occupation) }}"
                            class="form-control bg-dark text-white border-secondary" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white">Remarks</label>
                        <textarea name="remarks" class="form-control bg-dark text-white border-secondary"
                                  rows="2">{{ old('remarks', $occupation->remarks) }}</textarea>
                    </div>

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
