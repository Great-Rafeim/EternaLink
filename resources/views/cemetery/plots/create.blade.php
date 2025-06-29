<x-cemetery-layout>
    <div class="container" style="max-width:540px;">
        <div class="card bg-dark border-0 shadow-lg my-5">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-4 text-white">
                    <i class="bi bi-plus-square me-2"></i>
                    Add New Plot
                </h1>

                {{-- Error Alert --}}
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <div class="fw-bold mb-2">There were some issues with your input:</div>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('cemetery.plots.store') }}" method="POST" autocomplete="off">
                    @csrf

                    {{-- Plot input fields --}}
                    @include('cemetery.plots.forms.first')

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Plot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-cemetery-layout>
