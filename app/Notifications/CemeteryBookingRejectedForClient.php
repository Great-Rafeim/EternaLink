<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CemeteryBookingRejectedForClient extends Notification implements ShouldQueue
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
        $url = route('client.bookings.show', $this->cemeteryBooking->booking_id);

        return (new MailMessage)
            ->subject('Cemetery Booking Rejected')
            ->greeting('Dear Client,')
            ->line('We regret to inform you that your cemetery booking request was not approved.')
            ->line('If you need clarification or would like to make another arrangement, please contact us or your funeral coordinator.')
            ->action('View Your Booking', $url)
            ->line('Thank you for trusting us with your needs.')
->salutation('Regards,<br>EternaLink');
    }

    public function toArray($notifiable)
    {
        return [
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'booking_id'          => $this->cemeteryBooking->booking_id,
            'title'               => 'Cemetery Booking Rejected',
            'message'             => 'Your cemetery booking request was not approved. Please contact your coordinator for assistance.',
            'url'                 => route('client.bookings.show', $this->cemeteryBooking->booking_id),
        ];
    }
}
