<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CremationCertificateReady extends Notification
{
    use Queueable;

    protected $booking;
    protected $packageName;

    /**
     * Create a new notification instance.
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
        $this->packageName = $booking->package->name ?? 'No Package Selected';
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
        return (new MailMessage)
            ->subject('Cremation Certificate Ready for Booking #' . $this->booking->id)
            ->greeting('Hello ' . ($notifiable->name ?? '') . ',')
            ->line('The cremation certificate for your booking is now ready for download.')
            ->line('**Booking ID:** ' . $this->booking->id)
            ->line('**Package:** ' . $this->packageName)
            ->action('View Booking', route('client.bookings.show', $this->booking->id))
            ->line('Thank you for using our services!');
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id'   => $this->booking->id,
            'package_name' => $this->packageName,
            'title'        => 'Cremation Certificate Ready',
            'message'      => "The cremation certificate for booking #{$this->booking->id} ({$this->packageName}) is now ready for download.",
            'action_url'   => route('client.bookings.show', $this->booking->id),
        ];
    }
}
