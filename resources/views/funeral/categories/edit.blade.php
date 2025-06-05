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
                        <form method="POST" action="{{ route('funeral.categories.update', $category) }}">
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
</x-layouts.funeral>
