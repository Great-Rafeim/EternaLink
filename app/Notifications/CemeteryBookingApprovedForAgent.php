<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CemeteryBookingApprovedForAgent extends Notification implements ShouldQueue
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
        $this->plot = $cemeteryBooking->plot;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $showUrl = route('agent.bookings.show', $this->cemeteryBooking->booking_id);

        return (new MailMessage)
            ->subject('Cemetery Plot Approved')
            ->greeting('Hi Agent,')
            ->line('A plot for your client’s cemetery booking has been approved.')
            ->line('Plot: ' . ($this->plot->plot_number ?? 'N/A'))
            ->line('Section: ' . ($this->plot->section ?? 'N/A') . ', Block: ' . ($this->plot->block ?? 'N/A'))
            ->action('View Booking', $showUrl)
            ->line('Thank you.');
    }

    /**
     * Get the array representation of the notification (for database, etc.).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'plot_id'             => $this->plot->id ?? null,
            'plot_number'         => $this->plot->plot_number ?? null,
            'booking_id'          => $this->cemeteryBooking->booking_id,
            'message'             => 'A plot for your client’s cemetery booking has been approved.',
            'url'                 => route('agent.bookings.show', $this->cemeteryBooking->booking_id),
        ];
    }
}
