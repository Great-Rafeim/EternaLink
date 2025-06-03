<form action="{{ route('plots.updateReservation', $plot) }}" method="POST" class="space-y-4">
    @csrf
    @method('PUT')

    <!-- Existing fields -->
    <div>
        <label for="name" class="block text-white font-medium mb-1">Name *</label>
        <input type="text" id="name" name="name" required
               value="{{ old('name', $reservation->name ?? '') }}"
               class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" />
    </div>

    <div>
        <label for="purpose_of_reservation" class="block text-white font-medium mb-1">Purpose of Reservation</label>
        <select id="purpose_of_reservation" name="purpose_of_reservation"
                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600">
            <option value="">-- Select --</option>
            <option value="future_use" {{ old('purpose_of_reservation', $reservation->purpose_of_reservation ?? '') == 'future_use' ? 'selected' : '' }}>For Future Use</option>
            <option value="for_loved_one" {{ old('purpose_of_reservation', $reservation->purpose_of_reservation ?? '') == 'for_loved_one' ? 'selected' : '' }}>For Loved One</option>
            <option value="other" {{ old('purpose_of_reservation', $reservation->purpose_of_reservation ?? '') == 'other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>

    <div>
        <label for="contact_info" class="block text-white font-medium mb-1">Contact Info *</label>
        <input type="text" id="contact_info" name="contact_info" required
               value="{{ old('contact_info', $reservation->contact_info ?? '') }}"
               class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" />
    </div>

    <div>
        <label for="address" class="block text-white font-medium mb-1">Address</label>
        <input type="text" id="address" name="address"
               value="{{ old('address', $reservation->address ?? '') }}"
               class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" />
    </div>

    <div>
        <label for="identification_number" class="block text-white font-medium mb-1">Identification Number</label>
        <input type="text" id="identification_number" name="identification_number"
               value="{{ old('identification_number', $reservation->identification_number ?? '') }}"
               class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600" />
    </div>

    <div>
        <label for="notes" class="block text-white font-medium mb-1">Notes</label>
        <textarea name="notes" id="notes" rows="3"
                  class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600">{{ old('notes', $reservation->notes ?? '') }}</textarea>
    </div>

    <div class="mt-4">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold">
            Submit
        </button>
    </div>
</form>
