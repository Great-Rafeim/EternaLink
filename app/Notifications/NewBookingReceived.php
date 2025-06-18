<?php

// app/Notifications/NewBookingReceived.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;


class NewBookingReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "New booking received from <b>{$this->booking->client->name}</b> for <b>{$this->booking->package->name}</b>.",
            'booking_id' => $this->booking->id,
            'url' => route('funeral.bookings.show', $this->booking->id),
        ];
    }
}
