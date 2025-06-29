<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class BookingServiceUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $message;
    public $role; // 'client' or 'agent'

    /**
     * Create a new notification instance.
     */
    public function __construct($booking, $message, $role = 'client')
    {
        $this->booking = $booking;
        $this->message = $message;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification for storage.
     */
    public function toArray($notifiable)
    {
        return [
            'booking_id'        => $this->booking->id,
            'booking_reference' => $this->booking->id,
            'title'             => 'Service Update',
            'message'           => $this->message,
            'type'              => 'service_update',
            'updated_by'        => auth()->user()?->name ?? 'Funeral Parlor',
            'role'              => $this->role,
            'created_at'        => now()->toDateTimeString(),
            'url'               => $this->role === 'agent'
                ? route('agent.bookings.show', $this->booking->id)
                : route('client.bookings.show', $this->booking->id),
        ];
    }

    /**
     * Send email notification, role aware.
     */
    public function toMail($notifiable)
    {
        $url = $this->role === 'agent'
            ? route('agent.bookings.show', $this->booking->id)
            : route('client.bookings.show', $this->booking->id);

        return (new MailMessage)
            ->subject('Service Update for Booking #' . $this->booking->id)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line(strip_tags($this->message))
            ->action('View Booking', $url)
            ->line('Thank you for trusting our services.')
->salutation('Regards,<br>EternaLink');
    }
}
