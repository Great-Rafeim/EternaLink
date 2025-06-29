<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CemeteryBookingApprovedForClient extends Notification implements ShouldQueue
{
    use Queueable;

    public $cemeteryBooking;
    public $plot;

    /**
     * Create a new notification instance.
     */
    public function __construct($cemeteryBooking)
    {
        $this->cemeteryBooking = $cemeteryBooking;
        $this->plot = $cemeteryBooking->plot; // Assumes relationship loaded, otherwise can query here
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $bookingId = $this->cemeteryBooking->booking_id;
        $showUrl = route('client.bookings.show', $bookingId);

        return (new MailMessage)
            ->subject('Cemetery Plot Assigned')
            ->greeting('Dear Client,')
            ->line('Your cemetery booking has been approved and a plot has been assigned.')
            ->line('Plot Number: ' . ($this->plot->plot_number ?? 'N/A'))
            ->line('Section: ' . ($this->plot->section ?? 'N/A'))
            ->line('Block: ' . ($this->plot->block ?? 'N/A'))
            ->action('View Your Booking', $showUrl)
            ->line('Thank you for choosing our service!');
    }

    /**
     * Get the array representation of the notification (for database, etc.).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'plot_id'             => $this->plot->id ?? null,
            'plot_number'         => $this->plot->plot_number ?? null,
            'booking_id'          => $this->cemeteryBooking->booking_id,
            'message'             => 'Your cemetery booking has been approved and a plot has been assigned.',
            'url'                 => route('client.bookings.show', $this->cemeteryBooking->booking_id),
        ];
    }
}
