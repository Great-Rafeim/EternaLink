<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $role;

    /**
     * Create a new notification instance.
     */
    public function __construct($booking, $role = null)
    {
        $this->booking = $booking;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // Tailor the message by user role
        if ($this->role === 'client') {
            $subject = "Your Booking #{$this->booking->id} Has Been Paid";
            $greeting = "Hello!";
            $line = "Thank you for your payment. Your booking #{$this->booking->id} is now fully paid.";
        } elseif ($this->role === 'agent') {
            $subject = "A Client's Booking #{$this->booking->id} Has Been Paid";
            $greeting = "Good news!";
            $line = "Your assigned client's booking #{$this->booking->id} has been marked as paid.";
        } elseif ($this->role === 'funeral') {
            $subject = "A Booking #{$this->booking->id} Has Been Paid";
            $greeting = "Notice for Your Funeral Parlor";
            $line = "A booking in your funeral parlor (ID: {$this->booking->id}) has been paid.";
        } else {
            $subject = "Booking #{$this->booking->id} Has Been Paid";
            $greeting = "Notification";
            $line = "Booking #{$this->booking->id} has been marked as paid.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line)
            ->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Thank you for using EternaLink!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        if ($this->role === 'client') {
            $message = "Thank you! Your booking #{$this->booking->id} has been paid.";
        } elseif ($this->role === 'agent') {
            $message = "Booking #{$this->booking->id} for your assigned client has been paid.";
        } elseif ($this->role === 'funeral') {
            $message = "A booking (ID: {$this->booking->id}) in your funeral parlor has been paid.";
        } else {
            $message = "Booking #{$this->booking->id} has been paid.";
        }
        return [
            'message' => $message,
            'booking_id' => $this->booking->id,
        ];
    }
}
