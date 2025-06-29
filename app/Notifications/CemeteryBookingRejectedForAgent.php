<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CemeteryBookingRejectedForAgent extends Notification implements ShouldQueue
{
    use Queueable;

    public $cemeteryBooking;

    public function __construct($cemeteryBooking)
    {
        $this->cemeteryBooking = $cemeteryBooking;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $url = route('agent.bookings.show', $this->cemeteryBooking->booking_id);

        return (new MailMessage)
            ->subject('Cemetery Booking Rejected')
            ->greeting('Hello Agent,')
            ->line('The cemetery booking for your client has been rejected.')
            ->action('View Booking', $url)
                ->salutation('Regards,<br>EternaLink');
    }

    public function toArray($notifiable)
    {
        return [
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'booking_id'          => $this->cemeteryBooking->booking_id,
            'message'             => 'A cemetery booking for your client has been rejected.',
            'url'                 => route('agent.bookings.show', $this->cemeteryBooking->booking_id),
        ];
    }
}
