<form action="{{ route('plots.update', $plot) }}" method="POST" class="space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label for="plot_number" class="block text-white mb-1 font-medium">Plot Number *</label>
        <input type="text" name="plot_number" id="plot_number" value="{{ old('plot_number', $plot->plot_number ?? '') }}" required
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500" />
    </div>

    <div>
        <label for="section" class="block text-white mb-1 font-medium">Section</label>
        <input type="text" name="section" id="section" value="{{ old('section', $plot->section ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500" />
    </div>

    <div>
        <label for="block" class="block text-white mb-1 font-medium">Block</label>
        <input type="text" name="block" id="block" value="{{ old('block', $plot->block ?? '') }}"
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500" />
    </div>

    <div>
        <label for="type" class="block text-white mb-1 font-medium">Plot Type *</label>
        <select name="type" id="type" required
            class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-indigo-500">
            <option value="">-- Select Type --</option>
            <option value="single" {{ old('type', $plot->type ?? '') == 'single' ? 'selected' : '' }}>Single</option>
            <option value="double" {{ old('type', $plot->type ?? '') == 'double' ? 'selected' : '' }}>Double</option>
            <option value="niche" {{ old('type', $plot->type ?? '') == 'niche' ? 'selected' : '' }}>Niche</option>
        </select>
    </div>

    <div class="mt-4">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold">
            Save Changes
        </button>
    </div>
</form>
