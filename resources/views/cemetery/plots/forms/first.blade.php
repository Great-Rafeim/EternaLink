    <div class="mb-3">
        <label for="plot_number" class="form-label text-white">Plot Number *</label>
        <input type="text" name="plot_number" id="plot_number"
            value="{{ old('plot_number', $plot->plot_number ?? '') }}" required
            class="form-control bg-dark text-white border-secondary" />
    </div>

    <div class="mb-3">
        <label for="section" class="form-label text-white">Section</label>
        <input type="text" name="section" id="section"
            value="{{ old('section', $plot->section ?? '') }}"
            class="form-control bg-dark text-white border-secondary" />
    </div>

    <div class="mb-3">
        <label for="block" class="form-label text-white">Block</label>
        <input type="text" name="block" id="block"
            value="{{ old('block', $plot->block ?? '') }}"
            class="form-control bg-dark text-white border-secondary" />
    </div>

<div class="mb-3">
    <label for="type" class="form-label text-white">Plot Type *</label>
    <select name="type" id="type" required
        class="form-select bg-dark text-white border-secondary">
        <option value="">-- Select Type --</option>
        <option value="single" {{ old('type', $plot->type ?? '') == 'single' ? 'selected' : '' }}>Single</option>
        <option value="double" {{ old('type', $plot->type ?? '') == 'double' ? 'selected' : '' }}>Double</option>
        <option value="family" {{ old('type', $plot->type ?? '') == 'family' ? 'selected' : '' }}>Family</option>
    </select>
    @error('type')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>


