<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CemeteryBookingSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $cemeteryBookingId;

    public function __construct($cemeteryBookingId)
    {
        $this->cemeteryBookingId = $cemeteryBookingId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

public function toArray($notifiable)
{
    $cemeteryBooking = \App\Models\CemeteryBooking::find($this->cemeteryBookingId);

    $clientName = 'Client';
    if ($cemeteryBooking && $cemeteryBooking->user_id) {
        $clientUser = \App\Models\User::find($cemeteryBooking->user_id);
        if ($clientUser) {
            $clientName = $clientUser->name;
        }
    }

    return [
        'message'      => 'A new cemetery booking has been submitted by ' . $clientName,
        'cemetery_booking_id' => $cemeteryBooking ? $cemeteryBooking->id : null,
        'cemetery_id'  => $cemeteryBooking ? $cemeteryBooking->cemetery_id : null,
        'client_id'    => $cemeteryBooking ? $cemeteryBooking->user_id : null,
        'url'          => $cemeteryBooking ? route('cemetery.bookings.show', $cemeteryBooking->id) : null,
    ];
}

}
