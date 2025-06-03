<x-cemetery-layout>
    <h1 class="text-3xl font-bold mb-6">Cemetery Plots</h1>

    {{-- Success message --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-700 text-white rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter/Search Form --}}
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
        <input type="text" name="search" placeholder="Search plot #, owner or deceased"
            value="{{ request('search') }}"
            class="col-span-2 px-4 py-2 rounded border border-gray-700 bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-green-500" />

        <select name="status"
            class="col-span-1 px-4 py-2 rounded border border-gray-700 bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">All Statuses</option>
            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
            <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
            <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
        </select>

        <button type="submit"
            class="col-span-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Filter</button>

        <a href="{{ route('plots.index') }}"
            class="col-span-1 text-center bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Clear</a>

        <a href="{{ route('plots.create') }}"
            class="col-span-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Add New Plot</a>
    </form>

    {{-- Table of Plots --}}
    <div class="overflow-x-auto bg-gray-800 rounded-lg shadow-md">
        <table class="min-w-full divide-y divide-gray-700 text-sm">
            <thead class="bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Plot #</th>
                    <th class="px-4 py-3 text-left font-semibold">Section</th>
                    <th class="px-4 py-3 text-left font-semibold">Block</th>
                    <th class="px-4 py-3 text-left font-semibold">Type</th>
                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                    {{-- Removed Owner/Deceased header --}}
                    <th class="px-4 py-3 text-left font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($plots as $plot)
                    <tr class="hover:bg-gray-700">
                        <td class="px-4 py-2">{{ $plot->plot_number }}</td>
                        <td class="px-4 py-2">{{ $plot->section }}</td>
                        <td class="px-4 py-2">{{ $plot->block }}</td>
                        <td class="px-4 py-2">{{ ucfirst($plot->type) }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded font-semibold
                                {{ $plot->status === 'available' ? 'bg-green-600 text-white' :
                                   ($plot->status === 'reserved' ? 'bg-yellow-500 text-black' :
                                   'bg-red-600 text-white') }}">
                                {{ ucfirst($plot->status) }}
                            </span>
                        </td>
                        {{-- Removed Owner/Deceased data cell --}}
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('plots.edit', $plot) }}"
                                class="inline-block bg-yellow-500 hover:bg-yellow-600 px-3 py-1 text-xs text-black rounded">Update</a>

                            <form action="{{ route('plots.destroy', $plot) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Delete this plot?')">
                                @csrf
                                @method('DELETE')
                                <button class="bg-red-600 hover:bg-red-700 px-3 py-1 text-xs text-white rounded">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 px-4 py-6">No plots found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $plots->links() }}
    </div>
</x-cemetery-layout>
