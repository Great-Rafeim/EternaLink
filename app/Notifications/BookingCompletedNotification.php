<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use App\Models\AssetReservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;


class BookingCompletedNotification extends Notification
{
    use Queueable;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['mail', 'database']; // Use 'mail' if you want to send emails
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Funeral Service Completed')
            ->greeting('Dear ' . $notifiable->name)
            ->line('The funeral service for booking #' . $this->booking->id . ' has been marked as completed.')
            ->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Thank you.');
    }

    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'The funeral service for booking #' . $this->booking->id . ' has been marked as completed.',
        ];
    }
}
