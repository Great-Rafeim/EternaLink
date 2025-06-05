<x-layouts.funeral>
    <div class="max-w-5xl mx-auto p-6 bg-gray-800 rounded-lg shadow-lg text-white">
        <h2 class="text-2xl font-bold mb-4">Stock Movement Log: {{ $item->name }}</h2>

        <table class="min-w-full divide-y divide-gray-700 text-sm">
            <thead class="bg-gray-700 text-gray-300 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Qty</th>
                    <th class="px-4 py-2 text-left">Reason</th>
                    <th class="px-4 py-2 text-left">User</th>
                    <th class="px-4 py-2 text-left">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                @foreach($movements as $movement)
                    <tr>
                        <td class="px-4 py-2 capitalize">{{ $movement->type }}</td>
                        <td class="px-4 py-2">{{ $movement->quantity }}</td>
                        <td class="px-4 py-2">{{ $movement->reason }}</td>
                        <td class="px-4 py-2">{{ $movement->user->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $movement->created_at->format('M d, Y h:i A') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    </div>
</x-layouts.funeral>
