<?php

namespace App\Http\Controllers;

use App\Models\Plot;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\PlotOccupation;

class PlotController extends Controller
{
    public function index(Request $request)
    {
        $query = Plot::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('plot_number', 'like', "%{$search}%");
        }

        $plots = $query->orderBy('section')->orderBy('block')->paginate(15);

        return view('cemetery.plots.index', compact('plots'));
    }

    public function create()
    {
        return view('cemetery.plots.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plot_number' => 'required|unique:plots,plot_number|max:255',
            'section' => 'nullable|string|max:50',
            'block' => 'nullable|string|max:50',
            'type' => 'required|in:single,double,niche',
        ]);

        $validated['status'] = 'available';

        Plot::create($validated);

        return redirect()->route('plots.index')->with('success', 'Plot created successfully.');
    }

    public function edit(Plot $plot)
    {
        $plot->load([
            'reservation',
            'occupation',
            'reservationHistory',
            'occupationHistory'
        ]);

        return view('cemetery.plots.edit', compact('plot'));
    }


    public function update(Request $request, Plot $plot)
    {
        $validated = $request->validate([
            'plot_number' => 'required|max:255|unique:plots,plot_number,' . $plot->id,
            'section' => 'nullable|string|max:50',
            'block' => 'nullable|string|max:50',
            'type' => 'required|in:single,double,niche',
            'status' => 'required|in:available,reserved,occupied',
        ]);

        $plot->update($validated);

        return redirect()->route('plots.index')->with('success', 'Plot updated successfully.');
    }

    public function destroy(Plot $plot)
    {
        $plot->delete();

        return redirect()->route('plots.index')->with('success', 'Plot #' . $plot->plot_number . ' has been deleted.');
    }

    public function updateReservation(Request $request, Plot $plot)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'required|string|max:255',
            'purpose_of_reservation' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'identification_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $plot->update(['status' => 'reserved']);

        Reservation::updateOrCreate(
            ['plot_id' => $plot->id],
            array_merge($validated, ['plot_id' => $plot->id])
        );

        return redirect()->route('plots.edit', $plot)->with('success', 'Reservation updated successfully.');
    }

    public function updateOccupation(Request $request, Plot $plot)
    {
        $validated = $request->validate([
            'deceased_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date',
            'burial_date' => 'nullable|date',
            'cause_of_death' => 'nullable|string|max:255',
            'funeral_home' => 'nullable|string|max:255',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_contact' => 'nullable|string|max:255',
            'interred_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $plot->update(['status' => 'occupied']);

        PlotOccupation::updateOrCreate(
            ['plot_id' => $plot->id],
            array_merge($validated, ['plot_id' => $plot->id])
        );

        return redirect()->route('plots.edit', $plot)->with('success', 'Occupation info updated successfully.');
    }

    public function markAvailable(Plot $plot)
    {
        if ($plot->reservation) {
            $plot->reservation->update(['archived_at' => now()]);
        }

        if ($plot->occupation) {
            $plot->occupation->update(['archived_at' => now()]);
        }

        $plot->update(['status' => 'available']);

        return redirect()->route('plots.edit', $plot)->with('success', 'Plot marked as available. Previous records archived.');
    }



}
