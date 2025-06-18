<x-layouts.funeral>
    <div class="container py-5">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-lg rounded-4 p-4">
                <h2 class="fw-bold mb-3" style="color: #1565c0;">Edit Parlor Profile</h2>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('funeral.profile.update') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Image --}}
                    <div class="mb-3">
                        <label class="form-label">Profile Image / Logo</label>
                        <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(event)">
                        <div class="mt-2" id="image-preview">
                            @if(!empty($parlor->image))
                                <img src="{{ asset('storage/'.$parlor->image) }}" alt="Logo" class="img-thumbnail mb-2" style="max-height:80px;">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCurrentImage()">Remove Image</button>
                                <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                            @else
                                <div class="text-muted">No image uploaded.</div>
                                <input type="hidden" name="remove_image" id="remove-image-input" value="0">
                            @endif
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="mb-3">
                        <label class="form-label">Parlor Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                    </div>
                    {{-- Address --}}
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address', $parlor->address ?? '') }}" class="form-control">
                    </div>
                    {{-- Contact Email --}}
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $parlor->contact_email ?? '') }}" class="form-control">
                    </div>
                    {{-- Contact Number --}}
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" value="{{ old('contact_number', $parlor->contact_number ?? '') }}" class="form-control">
                    </div>
                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $parlor->description ?? '') }}</textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
function previewImage(event) {
    let preview = document.getElementById('image-preview');
    preview.innerHTML = "";
    if (event.target.files && event.target.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let img = document.createElement('img');
            img.src = e.target.result;
            img.className = "img-thumbnail mb-2";
            img.style.maxHeight = "80px";
            preview.appendChild(img);
        }
        reader.readAsDataURL(event.target.files[0]);
        let removeInput = document.getElementById('remove-image-input');
        if (removeInput) removeInput.value = "0";
        else {
            // if not exist, create it
            let hidden = document.createElement('input');
            hidden.type = "hidden";
            hidden.name = "remove_image";
            hidden.id = "remove-image-input";
            hidden.value = "0";
            preview.appendChild(hidden);
        }
    }
}
function removeCurrentImage() {
    let preview = document.getElementById('image-preview');
    preview.innerHTML = '<div class="text-danger mb-2">Image will be removed after update.</div>' +
        '<input type="hidden" name="remove_image" id="remove-image-input" value="1">';
}
</script>

</x-layouts.funeral>
    