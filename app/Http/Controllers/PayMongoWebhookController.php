<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Booking;

class PayMongoWebhookController extends Controller
{
public function webhook(Request $request)
{
    Log::info('[PayMongo Webhook] Incoming webhook:', $request->all());

    // Extract event type, payment ID, payment status, and reference number
    $eventType = $request->input('data.attributes.type');
    $paymentId = $request->input('data.attributes.data.id'); // pay_... or link_...
    $paymentStatus = $request->input('data.attributes.data.attributes.status'); // 'paid', etc.
    $referenceNumber = $request->input('data.attributes.data.attributes.external_reference_number')
        ?? $request->input('data.attributes.data.attributes.reference_number');

    Log::info('[PayMongo Webhook] Parsed event', [
        'event_type' => $eventType,
        'payment_id' => $paymentId,
        'payment_status' => $paymentStatus,
        'reference_number' => $referenceNumber,
    ]);

    // First try to find payment by PayMongo Payment ID
    $payment = Payment::where('reference_id', $paymentId)->first();

    // If not found, try to find payment by the reference number (short code)
    if (!$payment && $referenceNumber) {
        $payment = Payment::where('reference_number', $referenceNumber)->first();
    }

    if (!$payment) {
        Log::warning("[PayMongo Webhook] No matching payment for Payment ID: {$paymentId} or Reference Number: {$referenceNumber}");
        return response()->json(['ok' => true]);
    }

    Log::info('[PayMongo Webhook] Matching payment found', [
        'payment_id' => $payment->id,
        'booking_id' => $payment->booking_id,
        'current_status' => $payment->status,
    ]);

    // Handle 'paid' status
    if ($paymentStatus === 'paid') {
        Log::info('[PayMongo Webhook] Status is PAID. Checking if update is needed.', [
            'payment_status' => $payment->status,
        ]);

        if ($payment->status !== 'paid') {
            $payment->status = 'paid';
            $payment->paid_at = now();
            $payment->save();

            Log::info('[PayMongo Webhook] Payment marked as PAID.', [
                'payment_id' => $payment->id,
                'paid_at' => $payment->paid_at,
            ]);

            // Update booking status as well
            $booking = Booking::find($payment->booking_id);
            if ($booking) {
                $booking->status = 'paid';
                $booking->save();
                Log::info("[PayMongo Webhook] Booking marked as PAID.", [
                    'booking_id' => $booking->id,
                ]);

                // Send notifications after updating booking
                try {
                    // Notify client
                    if ($booking->client_user_id) {
                        $client = \App\Models\User::find($booking->client_user_id);
                        if ($client) {
                            $client->notify(new \App\Notifications\BookingPaidNotification($booking, 'client'));
                        }
                    }

                    // Notify funeral parlor (assuming you have funeral_user_id)
                    if ($booking->funeralHome) {
                        $booking->funeralHome->notify(
                            new \App\Notifications\BookingPaidNotification($booking, 'funeral')
                        );
                    }
                   // Notify agent (if using a bookingAgent relationship)
                    if ($booking->bookingAgent && $booking->bookingAgent->agent_user_id) {
                        $agentUser = \App\Models\User::find($booking->bookingAgent->agent_user_id);
                        if ($agentUser) {
                            $agentUser->notify(new \App\Notifications\BookingPaidNotification($booking, 'agent'));
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('[PayMongo Webhook] Error sending notifications: ' . $e->getMessage());
                }
            } else {
                Log::warning("[PayMongo Webhook] Booking not found for payment.", [
                    'booking_id' => $payment->booking_id,
                ]);
            }
        } else {
            Log::info("[PayMongo Webhook] Payment already marked as PAID. No action taken.", [
                'payment_id' => $payment->id,
            ]);
        }
    }
    // Handle failed/cancelled/expired/unpaid statuses
    elseif (in_array($paymentStatus, ['failed', 'cancelled', 'expired', 'unpaid'])) {
        Log::info('[PayMongo Webhook] Status is FAILED/CANCELLED/EXPIRED/UNPAID. Marking as failed.', [
            'status_received' => $paymentStatus,
        ]);

        $payment->status = 'failed';
        $payment->save();

        Log::info('[PayMongo Webhook] Payment marked as FAILED.', [
            'payment_id' => $payment->id,
        ]);

        if ($payment->booking_id) {
            $booking = Booking::find($payment->booking_id);
            if ($booking && $booking->status !== 'paid') {
                $booking->status = 'for_initial_review';
                $booking->save();
                Log::info("[PayMongo Webhook] Booking status set to FOR_INITIAL_REVIEW.", [
                    'booking_id' => $booking->id,
                ]);
            } else {
                Log::info("[PayMongo Webhook] Booking is already marked as PAID or not found. No action taken.", [
                    'booking_id' => $payment->booking_id,
                ]);
            }
        }
    } else {
        Log::info('[PayMongo Webhook] Webhook received unhandled status.', [
            'status' => $paymentStatus,
            'payment_id' => $paymentId,
        ]);
    }

    Log::info('[PayMongo Webhook] Processing complete. Responding OK.', [
        'payment_id' => $paymentId,
        'status' => $paymentStatus,
    ]);

    return response()->json(['ok' => true]);
}




}

