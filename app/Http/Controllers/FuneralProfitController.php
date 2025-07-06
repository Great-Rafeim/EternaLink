<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

class FuneralProfitController extends Controller
{
public function index(Request $request)
{
    $user = auth()->user();

    // 1. Get all bookings for this funeral home
    $bookings = \App\Models\Booking::where('funeral_home_id', $user->id)->get();

    // 2. Sum of ALL package values (all bookings, even if not paid) — NET of VAT
    $totalRawGross = $bookings->sum(function($b) {
        return $b->final_amount ?: 0;
    });
    $totalGross = $totalRawGross * 0.88; // 12% VAT removed

    // 3. Get only paid payments for this funeral home (join on booking_id)
    $paidPayments = \App\Models\Payment::where('status', 'paid')
        ->whereHas('booking', function($q) use ($user) {
            $q->where('funeral_home_id', $user->id);
        })
        ->with(['booking.client', 'booking.package']) // include package
        ->get();

    $totalRawPaid = $paidPayments->sum('amount');
    $totalPaid = $totalRawPaid * 0.88;

    // 4. Completion Rate
    $completionRate = $totalGross > 0 ? ($totalPaid / $totalGross) * 100 : 0;

    // 5. Breakdown table (show all payments for this funeral home)
    $allPayments = \App\Models\Payment::whereHas('booking', function($q) use ($user) {
            $q->where('funeral_home_id', $user->id);
        })
        ->with(['booking.client', 'booking.package'])
        ->latest('updated_at')
        ->get();

    $breakdown = $allPayments->map(function($payment) {
        $booking = $payment->booking;
        return [
            'booking_id'       => $booking?->id,
            'package'          => $booking?->package?->name ?? '—', // Added package name
            'client'           => $booking?->client?->name ?? '—',
            'amount'           => round(($payment->amount ?: 0) * 0.88, 2),
            'reference_number' => $payment->reference_number ?? '—',
            'status'           => ucfirst($payment->status),
            'paid_at'          => $payment->updated_at,
        ];
    });

    // 6. Monthly chart (group by month for paid only)
    $months = [];
    $chartData = [];
    $grouped = $paidPayments->groupBy(function($p) {
        return \Carbon\Carbon::parse($p->updated_at)->format('Y-m');
    });
    // Get last 12 months
    $now = \Carbon\Carbon::now();
    for ($i = 11; $i >= 0; $i--) {
        $month = $now->copy()->subMonths($i)->format('Y-m');
        $months[] = \Carbon\Carbon::parse($month . '-01')->format('M Y');
        $amountForMonth = isset($grouped[$month])
            ? $grouped[$month]->sum(function($p) { return ($p->amount ?: 0) * 0.88; })
            : 0;
        $chartData[] = round($amountForMonth, 2);
    }

    return view('funeral.profits.index', compact(
        'totalGross',
        'totalRawGross',
        'totalPaid',
        'totalRawPaid',
        'completionRate',
        'breakdown',
        'months',
        'chartData'
    ));
}


}
