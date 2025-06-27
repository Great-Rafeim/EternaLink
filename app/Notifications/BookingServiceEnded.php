<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue; // Optional: remove if you don't want queued notifications
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\AssetReservation;

class BookingServiceEnded extends Notification implements ShouldQueue // Uncomment if you want to queue
{
    use Queueable;

    public $booking;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($booking, $message)
    {
        $this->booking = $booking;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        // Send to database and optionally mail
        return ['database'];
    }

    /**
     * Get the array representation of the notification for storage.
     */
    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->id,
            'message' => $this->message,
            'title' => 'Service Completed',
            'type' => 'service_end',
            'updated_by' => auth()->user()?->name ?? 'Funeral Parlor',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * (Optional) If you want to email as well
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Funeral Service is Complete')
            ->line($this->message)
            ->action('View Booking', url(route('client.bookings.show', $this->booking->id)))
            ->line('Thank you for trusting our services.');
    }
}
