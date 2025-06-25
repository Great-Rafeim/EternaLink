<x-layouts.funeral>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card bg-dark text-white shadow-lg border-0 rounded-3">
                    <div class="card-body p-5">
                        <h2 class="mb-4 fw-bold text-warning d-flex align-items-center">
                            <i class="bi bi-pencil-square me-2"></i>
                            Edit Category
                        </h2>
                        <form method="POST" 
                              action="{{ route('funeral.categories.update', $category) }}" 
                              enctype="multipart/form-data" 
                              id="categoryEditForm">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label class="form-label text-light">Category Name</label>
                                <input type="text" name="name" value="{{ old('name', $category->name) }}"
                                    class="form-control bg-secondary text-white border-0 shadow-sm @error('name') is-invalid @enderror"
                                    required autocomplete="off">
                                @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-light">Description</label>
                                <textarea name="description" rows="3"
                                    class="form-control bg-secondary text-white border-0 shadow-sm @error('description') is-invalid @enderror"
                                    autocomplete="off">{{ old('description', $category->description) }}</textarea>
                                @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_asset" id="isAsset"
                                        value="1" {{ old('is_asset', $category->is_asset) ? 'checked' : '' }} onchange="toggleAssetFields()">
                                    <label class="form-check-label" for="isAsset">
                                        Mark as <strong>Bookable Asset</strong> (e.g., vehicle, chapel, freezer)
                                    </label>
                                    <div class="form-text text-light">
                                        Bookable assets are non-consumable and can only be scheduled or reserved, not depleted.
                                    </div>
                                </div>
                            </div>

                            <!-- Reservation Mode: Only show if asset is checked -->
                            <div class="mb-4" id="reservation-mode-row" style="{{ old('is_asset', $category->is_asset) ? '' : 'display:none' }}">
                                <label class="form-label">Reservation Mode <span class="text-danger">*</span></label>
                                <select name="reservation_mode"
                                        class="form-select bg-secondary border-0 text-white @error('reservation_mode') is-invalid @enderror">
                                    <option value="continuous" 
                                        {{ old('reservation_mode', $category->reservation_mode ?? 'continuous') == 'continuous' ? 'selected' : '' }}>
                                        Continuous (multi-day, e.g. Chapel, Viewing Room)
                                    </option>
                                    <option value="single_event" 
                                        {{ old('reservation_mode', $category->reservation_mode) == 'single_event' ? 'selected' : '' }}>
                                        Single Event (specific date/time, e.g. Transport, Burial Equipment)
                                    </option>
                                </select>
                                @error('reservation_mode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <div class="form-text text-light">
                                    <span class="fw-semibold">Continuous</span>: Reserved for a continuous range (e.g., several days for a wake).<br>
                                    <span class="fw-semibold">Single Event</span>: Reserved only for a specific date or time (e.g., transport on burial date).
                                </div>
                            </div>

                            <!-- Category Image Upload (only for asset) -->
                            <div class="mb-4" id="asset-image-row" style="{{ old('is_asset', $category->is_asset) ? '' : 'display:none' }}">
                                <label class="form-label text-white">Category Image</label>
                                <input type="file" name="image" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="previewCategoryImage(event)">
                                <div class="mt-2" id="category-image-preview">
                                    @if( old('remove_image') == "1" )
                                        <div class="text-muted">No image selected.</div>
                                    @elseif( old('image') )
                                        <div class="text-muted">Image will show after saving.</div>
                                    @elseif( $category->image )
                                        <img src="{{ asset('storage/'.$category->image) }}" alt="Current Image" class="img-thumbnail mb-2" style="max-height:120px;">
                                        <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="removeCurrentCategoryImage()">Remove Image</button>
                                    @else
                                        <div class="text-muted">No image selected.</div>
                                    @endif
                                    <input type="hidden" name="remove_image" id="remove-category-image-input" value="0">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="{{ route('funeral.categories.index') }}" class="btn btn-outline-light me-2">
                                    <i class="bi bi-arrow-left"></i> Cancel
                                </a>
                                <button class="btn btn-warning fw-semibold px-4 shadow-sm" type="submit">
                                    <i class="bi bi-save me-1"></i> Update Category
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

function removeCurrentCategoryImage() {
    let preview = document.getElementById('category-image-preview');
    preview.innerHTML = '<div class="text-muted">No image selected.</div>';
    document.getElementById('remove-category-image-input').value = "1";
}
</script>
</x-layouts.funeral>
