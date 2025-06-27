<x-cemetery-layout>
    <div class="container" style="max-width:600px;">
        <div class="card bg-dark border-0 shadow-lg my-5">
            <div class="card-body p-4">
                <h2 class="h4 fw-bold text-white mb-4">Edit Cemetery Information</h2>

                @if(session('success'))
                    <div class="alert alert-success text-white border-0 mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('cemetery.profile.update', $cemetery->id) }}" enctype="multipart/form-data" id="cemetery-edit-form">
                    @csrf
                    @method('PUT')

                    {{-- Cemetery Name (from User) --}}
                    <div class="mb-4">
                        <label class="form-label text-white fw-semibold">Cemetery Name</label>
                        <input type="text"
                               value="{{ $cemetery->user ? $cemetery->user->name : '-' }}"
                               class="form-control bg-dark text-white border-secondary"
                               readonly>
                        <div class="form-text text-light">
                            This name is managed from the user's profile.
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="mb-4">
                        <label class="form-label text-white fw-semibold">Address <span class="text-danger">*</span></label>
                        <input type="text" name="address"
                               value="{{ old('address', $cemetery->address) }}"
                               required
                               class="form-control bg-dark text-white border-secondary @error('address') border-danger @enderror">
                        @error('address')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Contact Number --}}
                    <div class="mb-4">
                        <label class="form-label text-white fw-semibold">Contact Number</label>
                        <input type="text" name="contact_number"
                               value="{{ old('contact_number', $cemetery->contact_number) }}"
                               class="form-control bg-dark text-white border-secondary @error('contact_number') border-danger @enderror">
                        @error('contact_number')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <label class="form-label text-white fw-semibold">Description</label>
                        <textarea name="description" rows="4"
                                  class="form-control bg-dark text-white border-secondary @error('description') border-danger @enderror"
                        >{{ old('description', $cemetery->description) }}</textarea>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Cemetery Image --}}
                    <div class="mb-5">
                        <label class="form-label text-white fw-semibold">Cemetery Image</label>
                        <div class="mb-2">
                            <input
                                type="file"
                                name="image"
                                id="cemetery-image-input"
                                accept="image/*"
                                class="form-control bg-dark text-white border-secondary"
                                onchange="previewCemeteryImage(this)"
                            >
                        </div>
                        <div id="cemetery-image-preview-container" class="mb-2">
                            @if(!empty($cemetery->image_path))
                                <img src="{{ asset('storage/'.$cemetery->image_path) }}"
                                     id="cemetery-image-preview"
                                     class="rounded border border-secondary shadow mb-2"
                                     style="max-height:160px;">
                                <button type="button"
                                        onclick="removeImagePreview()"
                                        class="btn btn-sm btn-danger">
                                    Remove Image
                                </button>
                            @else
                                <img src="" id="cemetery-image-preview" style="display:none;max-height:160px;" class="rounded border border-secondary shadow">
                            @endif
                        </div>
                        <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                        <div class="form-text text-light">
                            Allowed formats: JPG, PNG, GIF. Max size: 2MB.
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary px-5 fw-semibold">
                            Save Changes
                        </button>
                        <a href="{{ route('cemetery.dashboard') }}" class="text-info fw-semibold text-decoration-underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- JS for image preview/removal --}}
    <script>
        function previewCemeteryImage(input) {
            const preview = document.getElementById('cemetery-image-preview');
            const removeInput = document.getElementById('remove-image-input');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
                removeInput.value = '0';
            }
        }

        function removeImagePreview() {
            const preview = document.getElementById('cemetery-image-preview');
            const input = document.getElementById('cemetery-image-input');
            const removeInput = document.getElementById('remove-image-input');
            preview.src = '';
            preview.style.display = 'none';
            input.value = '';
            removeInput.value = '1';
        }
    </script>
</x-cemetery-layout>
