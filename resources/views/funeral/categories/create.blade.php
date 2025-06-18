<x-layouts.funeral>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="card bg-dark text-white shadow-lg border-0 rounded-3">
                    <div class="card-body">
                        <h2 class="mb-4 fw-bold">
                            <i class="bi bi-folder-plus me-2"></i>
                            Add Inventory Category
                        </h2>
                        <form method="POST" action="{{ route('funeral.categories.store') }}">
                            @csrf

                            <div class="mb-4">
                                <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="form-control bg-secondary border-0 text-white @error('name') is-invalid @enderror"
                                    placeholder="e.g. Coffins, Urns, Flowers" required autofocus>
                                @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3"
                                    class="form-control bg-secondary border-0 text-white @error('description') is-invalid @enderror"
                                    placeholder="(Optional) Describe this category">{{ old('description') }}</textarea>
                                @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_asset" id="isAsset"
                                        value="1" {{ old('is_asset') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isAsset">
                                        Mark as <strong>Bookable Asset</strong> (e.g., vehicle, chapel, freezer)
                                    </label>
                                    <div class="form-text text-light">
                                        Bookable assets are non-consumable and can only be scheduled or reserved, not depleted.
                                    </div>
                                </div>
                            </div>


                            <div class="d-flex justify-content-end">
                                <a href="{{ route('funeral.categories.index') }}" class="btn btn-outline-light me-2">
                                    <i class="bi bi-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary px-4 fw-semibold shadow">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Create Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.funeral>
