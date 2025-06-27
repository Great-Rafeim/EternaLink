<form action="{{ route('cemetery.plots.updateOccupation', $plot) }}" method="POST" class="space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label for="deceased_name" class="block text-white font-medium mb-1">Deceased Name *</label>
        <input
            type="text"
            id="deceased_name"
            name="deceased_name"
            value="{{ old('deceased_name', $occupation->deceased_name ?? '') }}"
            required
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >
    </div>

    @php
        $dateFields = ['birth_date', 'death_date', 'burial_date'];
    @endphp

    @foreach ($dateFields as $field)
        <div>
            <label for="{{ $field }}" class="block text-white font-medium mb-1 mt-4">{{ ucwords(str_replace('_', ' ', $field)) }}</label>
            <input
                type="date"
                id="{{ $field }}"
                name="{{ $field }}"
                value="{{ old($field, isset($occupation) && isset($occupation->$field) ? \Illuminate\Support\Carbon::parse($occupation->$field)->format('Y-m-d') : '') }}"
                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
            >
        </div>
    @endforeach

    <div>
        <label for="cause_of_death" class="block text-white font-medium mb-1 mt-4">Cause of Death</label>
        <input
            type="text"
            id="cause_of_death"
            name="cause_of_death"
            value="{{ old('cause_of_death', $occupation->cause_of_death ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >
    </div>

    <div>
        <label for="funeral_home" class="block text-white font-medium mb-1 mt-4">Funeral Home</label>
        <input
            type="text"
            id="funeral_home"
            name="funeral_home"
            value="{{ old('funeral_home', $occupation->funeral_home ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >
    </div>

    <div>
        <label for="next_of_kin_name" class="block text-white font-medium mb-1 mt-4">Next of Kin</label>
        <input
            type="text"
            id="next_of_kin_name"
            name="next_of_kin_name"
            value="{{ old('next_of_kin_name', $occupation->next_of_kin_name ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >
    </div>

    <div>
        <label for="next_of_kin_contact" class="block text-white font-medium mb-1 mt-4">Next of Kin Contact</label>
        <input
            type="text"
            id="next_of_kin_contact"
            name="next_of_kin_contact"
            value="{{ old('next_of_kin_contact', $occupation->next_of_kin_contact ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >
    </div>

    <div>
        <label for="interred_by" class="block text-white font-medium mb-1 mt-4">Interred By</label>
        <input
            type="text"
            id="interred_by"
            name="interred_by"
            value="{{ old('interred_by', $occupation->interred_by ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >
    </div>

    <div>
        <label for="notes" class="block text-white font-medium mb-1 mt-4">Notes</label>
        <textarea
            id="notes"
            name="notes"
            rows="3"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500"
        >{{ old('notes', $occupation->notes ?? '') }}</textarea>
    </div>

    <div class="mt-4">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold">
            Submit
        </button>
    </div>
</form>
