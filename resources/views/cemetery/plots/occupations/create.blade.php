<x-cemetery-layout>
    <div class="container" style="max-width: 620px;">
        <div class="card bg-dark border-0 shadow-lg my-5">
            <div class="card-body p-4">

                <h2 class="h4 fw-bold mb-3 text-white">
                    Assign Occupant to Plot #{{ $plot->plot_number }}
                </h2>

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

                {{-- Info --}}
                <div class="mb-4">
                    <div>
                        <span class="badge
                            {{ $plot->status === 'reserved' ? 'bg-warning text-dark' :
                               ($plot->status === 'occupied' ? 'bg-danger' : 'bg-success text-dark') }}">
                            {{ ucfirst($plot->status) }}
                        </span>
                        @if($plot->status === 'reserved' && isset($booking))
                            <span class="text-white-50 ms-2">
                                This plot is reserved. Autofill details from booking: <strong>#{{ $booking->id }}</strong>
                            </span>
                        @else
                            <span class="text-white-50 ms-2">
                                Manual entry enabled.
                            </span>
                        @endif
                    </div>
                </div>

                <form
                    action="{{ route('cemetery.plots.occupations.store', $plot) }}"
                    method="POST"
                    autocomplete="off"
                >
                    @csrf

                    {{-- Deceased Details --}}
                    <div class="mb-3">
                        <label for="deceased_first_name" class="form-label text-white">
                            First Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="deceased_first_name" id="deceased_first_name"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_first_name', $prefill['deceased_first_name'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_middle_name" class="form-label text-white">Middle Name</label>
                        <input type="text" name="deceased_middle_name" id="deceased_middle_name"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_middle_name', $prefill['deceased_middle_name'] ?? '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="deceased_last_name" class="form-label text-white">
                            Last Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="deceased_last_name" id="deceased_last_name"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_last_name', $prefill['deceased_last_name'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_nickname" class="form-label text-white">Nickname</label>
                        <input type="text" name="deceased_nickname" id="deceased_nickname"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_nickname', $prefill['deceased_nickname'] ?? '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="deceased_sex" class="form-label text-white">
                            Sex <span class="text-danger">*</span>
                        </label>
                        <select name="deceased_sex" id="deceased_sex"
                                class="form-select bg-dark text-white border-secondary"
                                required>
                            <option value="">-- Select --</option>
                            <option value="Male"
                                {{ in_array(strtolower(old('deceased_sex', $prefill['deceased_sex'] ?? '')), ['male', 'm']) ? 'selected' : '' }}>
                                Male
                            </option>
                            <option value="Female"
                                {{ in_array(strtolower(old('deceased_sex', $prefill['deceased_sex'] ?? '')), ['female', 'f']) ? 'selected' : '' }}>
                                Female
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_birthday" class="form-label text-white">
                            Birthday <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="deceased_birthday" id="deceased_birthday"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_birthday', $prefill['deceased_birthday'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_date_of_death" class="form-label text-white">
                            Date of Death <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="deceased_date_of_death" id="deceased_date_of_death"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_date_of_death', $prefill['deceased_date_of_death'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_age" class="form-label text-white">
                            Age <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="deceased_age" id="deceased_age" min="0" max="150"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_age', $prefill['deceased_age'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_civil_status" class="form-label text-white">
                            Civil Status <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="deceased_civil_status" id="deceased_civil_status"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_civil_status', $prefill['deceased_civil_status'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_residence" class="form-label text-white">
                            Residence <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="deceased_residence" id="deceased_residence"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_residence', $prefill['deceased_residence'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="deceased_citizenship" class="form-label text-white">
                            Citizenship <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="deceased_citizenship" id="deceased_citizenship"
                            class="form-control bg-dark text-white border-secondary"
                            value="{{ old('deceased_citizenship', $prefill['deceased_citizenship'] ?? '') }}"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label text-white">Remarks</label>
                        <textarea name="remarks" id="remarks"
                            class="form-control bg-dark text-white border-secondary"
                            rows="2">{{ old('remarks', $prefill['remarks'] ?? '') }}</textarea>
                    </div>

                    {{-- Booking link (hidden) --}}
                    @if(isset($prefill['booking_id']))
                        <input type="hidden" name="booking_id" value="{{ $prefill['booking_id'] }}">
                        <div class="alert alert-info py-2 px-3 mb-4 small">
                            Linked to Booking #{{ $prefill['booking_id'] }}
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('cemetery.plots.edit', $plot) }}"
                           class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Plot
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save Occupation Info
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-cemetery-layout>
