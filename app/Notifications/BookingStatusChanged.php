<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Booking;

/**
 * Notification for Booking Status Change
 */
class BookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \App\Models\Booking
     */
    protected $booking;

    /**
     * @var string|null
     */
    protected $customMessage;

    /**
     * @var string|null
     */
    protected $role; // e.g. 'client', 'agent', 'funeral'

    /**
     * Create a new notification instance.
     * @param Booking $booking
     * @param string|null $customMessage
     * @param string|null $role
     */
    public function __construct(Booking $booking, $customMessage = null, $role = null)
    {
        $this->booking = $booking;
        $this->customMessage = $customMessage;
        $this->role = $role; // explicitly pass if needed, or auto-detect
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        // Add 'mail' here if you want to email as well
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toDatabase($notifiable)
    {
        // Fallbacks
        $packageName = $this->booking->package->name ?? 'Service Package';
        $status = ucfirst($this->booking->status);
        $role = $this->role ?? $this->detectRole($notifiable);

        // Smart route/link selection based on recipient role
        $url = $this->bookingUrlFor($role);

        $message = $this->customMessage
            ?: "Your booking for <b>{$packageName}</b> is now <b>{$status}</b>.";

        return [
            'message'     => $message,
            'booking_id'  => $this->booking->id,
            'status'      => $status,
            'url'         => $url,
            'role'        => $role,
        ];
    }

    /**
     * Optional: mail notification (uncomment 'mail' in via() to use)
     */

    public function toMail($notifiable)
    {
        $packageName = $this->booking->package->name ?? 'Service Package';
        $status = ucfirst($this->booking->status);
        $role = $this->role ?? $this->detectRole($notifiable);

        $url = $this->bookingUrlFor($role);

        $message = strip_tags($this->customMessage
            ?: "Your booking for {$packageName} is now {$status}.");

        return (new MailMessage)
            ->subject('Booking Status Update')
            ->greeting('Hello!')
            ->line($message)
            ->action('View Booking', $url)
            ->line('Thank you for using our service.')
->salutation('Regards,<br>EternaLink');
    }
    

    /**
     * Helper: Detect role based on notifiable
     */
    protected function detectRole($notifiable)
    {
        // Assuming you have 'role' column on User
        return $notifiable->role ?? null;
    }

    /**
     * Helper: Generate correct booking details URL for user role
     */
    protected function bookingUrlFor($role)
    {
        switch ($role) {
            case 'funeral':
                return route('funeral.bookings.show', $this->booking->id);
            case 'agent':
                // Use your agent route if exists
                return route('agent.bookings.show', $this->booking->id);
            case 'client':
            default:
                return route('client.bookings.show', $this->booking->id);
        }
    }
}
