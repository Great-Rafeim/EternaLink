<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partnership;
use App\Models\User;

class PartnershipController extends Controller
{
public function index()
{
    $user = auth()->user();

    $sentRequests = $user->sentPartnershipRequests()
        ->with(['partner', 'partner.funeralParlor'])
        ->where('status', '!=', 'rejected')
        ->get();

    $receivedRequests = $user->receivedPartnershipRequests()
        ->with(['requester', 'requester.funeralParlor'])
        ->where('status', 'pending')
        ->get();

    $activePartnerships = Partnership::where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)
              ->orWhere('partner_id', $user->id);
        })
        ->where('status', 'accepted')
        ->with([
            'requester', 'partner',
            'requester.funeralParlor', 'partner.funeralParlor'
        ])
        ->get();

    return view('funeral.partnerships.index', compact(
        'activePartnerships',
        'sentRequests',
        'receivedRequests'
    ));
}


    public function find(Request $request)
    {
        $user = auth()->user();

        $alreadyPartneredIds = Partnership::where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('partner_id')
            ->merge(
                Partnership::where('partner_id', $user->id)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->pluck('requester_id')
            )
            ->unique()
            ->push($user->id);


        $query = User::where('role', 'funeral')
                     ->whereNotIn('id', $alreadyPartneredIds);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%");
        }

        $potentialPartners = $query->paginate(10);

        return view('funeral.partnerships.find', compact('potentialPartners', 'search'));
    }

    public function sendRequest(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:users,id'
        ]);
        $user = auth()->user();

        $exists = Partnership::where(function($q) use ($user, $request) {
            $q->where(function($query) use ($user, $request) {
                $query->where('requester_id', $user->id)
                    ->where('partner_id', $request->partner_id);
            })->orWhere(function($query) use ($user, $request) {
                $query->where('requester_id', $request->partner_id)
                    ->where('partner_id', $user->id);
            });
        })->whereIn('status', ['pending', 'accepted'])->exists();

        if ($exists) {
            return back()->with('error', 'Partnership already exists or is pending.');
        }

        Partnership::create([
            'requester_id' => $user->id,
            'partner_id' => $request->partner_id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Partnership request sent!');
    }



    public function destroy($id)
    {
        $user = auth()->user();
        $partnership = \App\Models\Partnership::where('id', $id)
            ->where(function($q) use ($user) {
                $q->where('requester_id', $user->id)
                ->orWhere('partner_id', $user->id);
            })
            ->firstOrFail();

        // Only allow cancellation if pending or dissolution if accepted
        if ($partnership->status === 'pending' && $partnership->requester_id === $user->id) {
            $partnership->delete();
            return back()->with('success', 'Partnership request cancelled.');
        } elseif ($partnership->status === 'accepted') {
            $partnership->delete();
            return back()->with('success', 'Partnership dissolved.');
        }

        return back()->with('error', 'You are not allowed to perform this action.');
    }


    public function respond(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:accept,reject'
        ]);
        $user = auth()->user();

        // Only the recipient can respond
        $partnership = \App\Models\Partnership::where('id', $id)
            ->where('partner_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($request->action === 'accept') {
            $partnership->status = 'accepted';
            $partnership->save();
            return back()->with('success', 'Partnership accepted.');
        } else {
            $partnership->status = 'rejected';
            $partnership->save();
            return back()->with('success', 'Partnership rejected.');
        }
    }


}
