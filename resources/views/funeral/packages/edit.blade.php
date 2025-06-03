<x-layouts.funeral>
    <div class="container py-4">
        <h2>Edit Funeral Service Package</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('funeral.packages.update', $package->id) }}">
            @csrf
            @method('PUT')

            @include('funeral.packages.partials.form', [
                'package' => $package,
                'isEdit' => true
            ])
        </form>
    </div>

    @include('funeral.packages.partials.script')
</x-layouts.funeral>
