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
                        <form method="POST" action="{{ route('funeral.categories.store') }}" enctype="multipart/form-data" id="categoryForm">
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
                                        value="1" {{ old('is_asset') ? 'checked' : '' }} onchange="toggleAssetFields()">
                                    <label class="form-check-label" for="isAsset">
                                        Mark as <strong>Bookable Asset</strong> (e.g., vehicle, chapel, freezer)
                                    </label>
                                    <div class="form-text text-light">
                                        Bookable assets are non-consumable and can only be scheduled or reserved, not depleted.
                                    </div>
                                </div>
                            </div>

                            <!-- Reservation Mode: Only show if asset is checked -->
                            <div class="mb-4" id="reservation-mode-row" style="{{ old('is_asset') ? '' : 'display:none' }}">
                                <label class="form-label">Reservation Mode <span class="text-danger">*</span></label>
                                <select name="reservation_mode"
                                        class="form-select bg-secondary border-0 text-white @error('reservation_mode') is-invalid @enderror">
                                    <option value="continuous" {{ old('reservation_mode', 'continuous') == 'continuous' ? 'selected' : '' }}>
                                        Continuous (multi-day, e.g. Chapel, Viewing Room)
                                    </option>
                                    <option value="single_event" {{ old('reservation_mode') == 'single_event' ? 'selected' : '' }}>
                                        Single Event (specific date/time, e.g. Transport, Burial Equipment)
                                    </option>
                                </select>
                                @error('reservation_mode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <div class="form-text text-light">
                                    <span class="fw-semibold">Continuous</span>: Reserved for a continuous range (e.g., several days for a wake).<br>
                                    <span class="fw-semibold">Single Event</span>: Reserved only for a specific date or time (e.g., transport on burial date).
                                </div>
                            </div>

                            <!-- Asset Category Image Upload (only if asset) -->
                            <div class="mb-4" id="asset-image-row" style="{{ old('is_asset') ? '' : 'display:none' }}">
                                <label class="form-label text-white">Category Image</label>
                                <input type="file" name="image" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="previewCategoryImage(event)">
                                <div class="mt-2" id="category-image-preview">
                                    <div class="text-muted">No image selected.</div>
                                    <input type="hidden" name="remove_image" id="remove-category-image-input" value="0">
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

<script>
function toggleAssetFields() {
    let isAsset = document.getElementById('isAsset').checked;
    document.getElementById('reservation-mode-row').style.display = isAsset ? '' : 'none';
    document.getElementById('asset-image-row').style.display = isAsset ? '' : 'none';
    // If unchecked, reset image field and preview
    if (!isAsset) {
        let imgInput = document.querySelector('input[type=file][name=image]');
        if(imgInput) imgInput.value = "";
        document.getElementById('remove-category-image-input').value = "0";
        document.getElementById('category-image-preview').innerHTML = '<div class="text-muted">No image selected.</div>';
    }
}

window.addEventListener('DOMContentLoaded', toggleAssetFields);

function previewCategoryImage(event) {
    let preview = document.getElementById('category-image-preview');
    preview.innerHTML = "";
    if (event.target.files && event.target.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let img = document.createElement('img');
            img.src = e.target.result;
            img.className = "img-thumbnail mb-2";
            img.style.maxHeight = "120px";
            preview.appendChild(img);

            // Add remove button
            let btn = document.createElement('button');
            btn.type = "button";
            btn.className = "btn btn-outline-danger btn-sm ms-2";
            btn.innerHTML = "Remove Image";
            btn.onclick = function() {
                document.querySelector('input[type=file][name=image]').value = "";
                preview.innerHTML = '<div class="text-muted">No image selected.</div>';
                document.getElementById('remove-category-image-input').value = "1";
            };
            preview.appendChild(btn);
        }
        reader.readAsDataURL(event.target.files[0]);
        document.getElementById('remove-category-image-input').value = "0";
    }
}
</script>
</x-layouts.funeral>
