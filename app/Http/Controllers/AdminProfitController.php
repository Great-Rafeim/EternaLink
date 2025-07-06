<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;

class AdminProfitController extends Controller
{
public function index(Request $request)
{
    // Get all payments with related booking/funeralHome/client
    $allPayments = \App\Models\Payment::with(['booking.funeralHome', 'booking.client'])
        ->latest('updated_at')
        ->get();

    // Counts for paid and pending
    $totalPaid = $allPayments->where('status', 'paid')->count();
    $totalPending = $allPayments->where('status', 'pending')->count();

    // Profit is sum of paid convenience fees
    $totalProfit = $allPayments->where('status', 'paid')->sum('convenience_fee');

    // Total amount handled by the system (all payments)
    $totalPackageValue = $allPayments->sum('amount');

    // Breakdown for the table
    $breakdown = $allPayments->map(function($payment) {
        $booking = $payment->booking;
        return [
            'booking_id'       => $booking?->id,
            'reference_number' => $payment->reference_number ?? '—',
            'funeral_home'     => $booking?->funeralHome?->name ?? $booking?->funeralHome?->email ?? 'N/A',
            'client'           => $booking?->client?->name ?? $booking?->client?->email ?? 'N/A',
            'convenience_fee'  => $payment->convenience_fee,
            'amount'           => $payment->amount,
            'status'           => $payment->status,
            'paid_at'          => $payment->updated_at,
        ];
    });

    return view('admin.profits.index', compact(
        'totalPaid',
        'totalPending',
        'totalProfit',
        'totalPackageValue',
        'breakdown'
    ));
}

}
