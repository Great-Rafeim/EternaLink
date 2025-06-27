<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class CemeteryBookingSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    // This is the critical method for database notifications!
    public function toDatabase($notifiable)
    {
        return [
            'message'      => 'A new cemetery booking has been submitted by ' . $this->booking->user->name,
            'booking_id'   => $this->booking->id,
            'cemetery_id'  => $this->booking->cemetery_id,
            'url'          => route('cemetery.bookings.show', $this->booking->id),
        ];
    }

    // Optional: keep for other channels if you use them (e.g. mail, broadcast)
    public function toArray($notifiable)
    {
        return [
            'message'      => 'A new cemetery booking has been submitted by ' . $this->booking->user->name,
            'booking_id'   => $this->booking->id,
            'cemetery_id'  => $this->booking->cemetery_id,
            'url'          => route('cemetery.bookings.show', $this->booking->id),
        ];
    }
}
