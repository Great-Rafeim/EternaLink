<h2 class="text-xl font-semibold mb-4">Reservation History</h2>
@if($reservationHistory->isEmpty())
    <p class="text-gray-400">No past reservations.</p>
@else
    <ul class="space-y-2">
        @foreach($reservationHistory as $reservation)
            <li class="bg-gray-700 p-4 rounded">
                <div><strong>Name:</strong> {{ $reservation->name }}</div>
                <div><strong>Contact:</strong> {{ $reservation->contact_info }}</div>
                <div><strong>Purpose:</strong> {{ $reservation->purpose_of_reservation }}</div>
                <div><strong>Archived At:</strong> {{ $reservation->archived_at }}</div>
            </li>
        @endforeach
    </ul>
@endif

<hr class="my-6 border-gray-600">

<h2 class="text-xl font-semibold mb-4">Occupation History</h2>
@if($occupationHistory->isEmpty())
    <p class="text-gray-400">No past occupations.</p>
@else
    <ul class="space-y-2">
        @foreach($occupationHistory as $occupation)
            <li class="bg-gray-700 p-4 rounded">
                <div><strong>Deceased:</strong> {{ $occupation->deceased_name }}</div>
                <div><strong>Birth:</strong> {{ optional($occupation->birth_date)->format('Y-m-d') }}</div>
                <div><strong>Death:</strong> {{ optional($occupation->death_date)->format('Y-m-d') }}</div>
                <div><strong>Buried:</strong> {{ optional($occupation->burial_date)->format('Y-m-d') }}</div>
                <div><strong>Archived At:</strong> {{ $occupation->archived_at }}</div>
            </li>
        @endforeach
    </ul>
@endif
