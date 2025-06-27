<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CemeteryBookingApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $notifData;

    /**
     * Create a new notification instance.
     *
     * @param array $notifData
     */
    public function __construct(array $notifData)
    {
        $this->notifData = $notifData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cemetery Booking Approved')
            ->greeting('Hello!')
            ->line('Your cemetery booking has been approved and a plot has been assigned to you.')
            ->line('Booking ID: ' . ($this->notifData['booking_id'] ?? ''))
            ->line('Plot Number: ' . ($this->notifData['plot_number'] ?? ''))
            ->action('View Booking', url('/client/dashboard'))
            ->line('Thank you for using EternaLink!');
    }

    /**
     * Get the array representation of the notification (database).
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->notifData['title'] ?? 'Cemetery Booking Approved',
            'message' => $this->notifData['message'] ?? 'Your cemetery booking has been approved and a plot assigned.',
            'booking_id' => $this->notifData['booking_id'] ?? null,
            'plot_number' => $this->notifData['plot_number'] ?? null,
        ];
    }
}
