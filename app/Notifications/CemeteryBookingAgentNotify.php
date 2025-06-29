<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue; // <-- Add this

class CemeteryBookingAgentNotify extends Notification implements ShouldQueue // <-- Implement ShouldQueue
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

    public function toArray($notifiable)
    {
        return [
            'message'      => 'A client you are assisting has submitted a cemetery booking.',
            'booking_id'   => $this->booking->id,
            'cemetery_id'  => $this->booking->cemetery_id,
            'url'          => route('agent.bookings.show', $this->booking->id),
        ];
    }
}


