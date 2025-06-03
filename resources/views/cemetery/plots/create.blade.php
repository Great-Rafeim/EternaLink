<x-cemetery-layout>
    <div class="container mx-auto max-w-lg p-6 bg-gray-800 rounded-lg shadow-lg">
        <h1 class="text-2xl font-semibold mb-6 text-white">Add New Plot</h1>

        @if ($errors->any())
            <div class="bg-red-600 text-white p-3 rounded mb-4">
                <strong>There were some issues with your input:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('plots.store') }}" method="POST" class="space-y-4">
            @csrf

            @include('cemetery.plots.forms.first')

            <div class="flex justify-between items-center mt-6">
                <a href="{{ route('plots.index') }}" class="text-indigo-400 hover:text-indigo-600 font-medium">Cancel</a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold">
                    Save Plot
                </button>
            </div>
        </form>
    </div>
</x-cemetery-layout>
