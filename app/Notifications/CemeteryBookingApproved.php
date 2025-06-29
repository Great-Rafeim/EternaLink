<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

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
        // Pick booking view route by role (if provided)
        $role = $this->notifData['role'] ?? ($notifiable->role ?? null);
        $bookingId = $this->notifData['booking_id'] ?? null;

        if ($role === 'funeral') {
            $url = route('funeral.bookings.show', $bookingId);
        } elseif ($role === 'agent') {
            $url = route('agent.bookings.show', $bookingId);
        } else {
            $url = route('client.bookings.show', $bookingId);
        }

        // Message content
        $client = $this->notifData['client_name'] ?? 'Client';
        $cemetery = $this->notifData['cemetery_name'] ?? 'Cemetery';
        $plot = $this->notifData['plot_number'] ?? 'N/A';

        $line1 = match ($role) {
            'client'  => "Dear <b>{$client}</b>, your cemetery booking at <b>{$cemetery}</b> has been approved and plot <b>#{$plot}</b> assigned.",
            'funeral' => "A cemetery booking for <b>{$client}</b> at <b>{$cemetery}</b> has been approved. Plot assigned: <b>#{$plot}</b>.",
            'agent'   => "Your client <b>{$client}</b> had a cemetery booking approved at <b>{$cemetery}</b>. Plot assigned: <b>#{$plot}</b>.",
            default   => "Your cemetery booking has been approved and a plot assigned."
        };

        return (new MailMessage)
            ->subject('Cemetery Booking Approved')
            ->greeting('Hello!')
            ->line(strip_tags($line1))
            ->action('View Booking', $url)
            ->line('Thank you for using EternaLink!')
->salutation('Regards,<br>EternaLink');
    }

    /**
     * Get the array representation of the notification (database).
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $role = $this->notifData['role'] ?? ($notifiable->role ?? null);
        $client = $this->notifData['client_name'] ?? 'Client';
        $cemetery = $this->notifData['cemetery_name'] ?? 'Cemetery';
        $plot = $this->notifData['plot_number'] ?? 'N/A';

        $message = match ($role) {
            'client'  => "Dear <b>{$client}</b>, your cemetery booking at <b>{$cemetery}</b> has been <b>APPROVED</b> and plot <b>#{$plot}</b> assigned.",
            'funeral' => "A cemetery booking for <b>{$client}</b> at <b>{$cemetery}</b> has been <b>APPROVED</b>. Plot assigned: <b>#{$plot}</b>.",
            'agent'   => "Your client <b>{$client}</b> had a cemetery booking approved at <b>{$cemetery}</b>. Plot assigned: <b>#{$plot}</b>.",
            default   => "Your cemetery booking has been approved and a plot assigned.",
        };

        // Use the correct route for database notification
        $bookingId = $this->notifData['booking_id'] ?? null;
        if ($role === 'funeral') {
            $url = route('funeral.bookings.show', $bookingId);
        } elseif ($role === 'agent') {
            $url = route('agent.bookings.show', $bookingId);
        } else {
            $url = route('client.bookings.show', $bookingId);
        }

        return [
            'title'         => $this->notifData['title'] ?? 'Cemetery Booking Approved',
            'message'       => $message,
            'booking_id'    => $bookingId,
            'plot_number'   => $plot,
            'client_name'   => $client,
            'cemetery_name' => $cemetery,
            'role'          => $role,
            'url'           => $url,
        ];
    }
}
        