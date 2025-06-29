<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Booking; // <-- Add this

class BookingCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $role;

    public function __construct(Booking $booking, $role = null)
    {
        $this->booking = $booking;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $clientName = $this->booking->client->name ?? 'the client';
        $parlorName = $this->booking->funeralHome->name ?? 'Funeral Parlor';
        $bookingId = $this->booking->id;
        $role = $this->role ?? ($notifiable->role ?? null);

        if ($role === 'agent') {
            $line = "The funeral service for booking #{$bookingId} for your client <b>{$clientName}</b> at <b>{$parlorName}</b> has been marked as completed.";
        } elseif ($role === 'funeral') {
            $line = "The funeral service you handled for booking #{$bookingId} (client: <b>{$clientName}</b>) has been marked as completed.";
        } else {
            $line = "The funeral service for your booking #{$bookingId} at <b>{$parlorName}</b> has been marked as completed.";
        }

        return (new MailMessage)
            ->subject('Funeral Service Completed')
            ->greeting('Dear ' . $notifiable->name)
            ->line(strip_tags($line))
            ->action('View Booking', url('/bookings/' . $bookingId))
            ->line('Thank you.')
            ->salutation('Regards,<br>EternaLink');

    }

    public function toArray($notifiable)
    {
        $clientName = $this->booking->client->name ?? 'the client';
        $parlorName = $this->booking->funeralHome->name ?? 'Funeral Parlor';
        $bookingId = $this->booking->id;
        $role = $this->role ?? ($notifiable->role ?? null);

        if ($role === 'agent') {
            $msg = "The funeral service for booking <b>#{$bookingId}</b> for your client <b>{$clientName}</b> at <b>{$parlorName}</b> has been marked as completed.";
        } elseif ($role === 'funeral') {
            $msg = "The funeral service you handled for booking <b>#{$bookingId}</b> (client: <b>{$clientName}</b>) has been marked as completed.";
        } else {
            $msg = "The funeral service for your booking <b>#{$bookingId}</b> at <b>{$parlorName}</b> has been marked as completed.";
        }

        return [
            'booking_id' => $bookingId,
            'message' => $msg,
        ];
    }
}
