<x-cemetery-layout>
    <div class="container" style="max-width: 540px;">
        <div class="card bg-dark border-0 shadow-lg my-5">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-4 text-white">Add New Plot</h1>

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

                <form action="{{ route('cemetery.plots.store') }}" method="POST" autocomplete="off">
                    @csrf

                    @include('cemetery.plots.forms.first')

                    <div class="d-flex justify-content-between align-items-center mt-4">

    <div class="mt-4 text-end">
        <button type="submit"
            class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i>
            Save Plot
        </button>
        
    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-cemetery-layout>
