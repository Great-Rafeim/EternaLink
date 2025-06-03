<x-layouts.funeral>
<div class="container py-4">
    <h2>Create Funeral Service Package</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('packages.store') }}">
        @csrf

        @include('funeral.packages.partials.form')
    </form>
</div>

@include('funeral.packages.partials.script')
</x-layouts.funeral>
