<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Booking;
use App\Models\CustomizedPackage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomizationRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $customizedPackage;
    protected $customMessage;
    protected $role;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, CustomizedPackage $customizedPackage, $customMessage = null, $role = null)
    {
        $this->booking = $booking;
        $this->customizedPackage = $customizedPackage;
        $this->customMessage = $customMessage;
        $this->role = $role;
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
     * Get the array representation of the notification for the database.
     */
    public function toArray($notifiable)
    {
        $parlorName = $this->booking->funeralHome->name ?? 'Funeral Parlor';
        $clientName = $this->booking->client->name ?? 'the client';
        $role = $this->role ?: ($notifiable->role ?? null);

        // Use the custom message if set, otherwise build default per role
        if ($this->customMessage) {
            $message = $this->customMessage;
        } elseif ($role === 'agent') {
            $message = "The customization request for booking <b>#{$this->booking->id}</b> for client <b>{$clientName}</b> at <b>{$parlorName}</b> was <b>APPROVED</b> by the funeral parlor. The updated package and pricing have been applied.";
        } else { // client or fallback
            $message = "Your customization request for booking <b>#{$this->booking->id}</b> at <b>{$parlorName}</b> was <b>APPROVED</b>. The updated package and pricing are now in effect.";
        }

        // Choose correct booking URL based on role
        $url = $this->bookingUrlFor($role);

        return [
            'title'                  => 'Customization Approved',
            'message'                => $message,
            'booking_id'             => $this->booking->id,
            'customized_package_id'  => $this->customizedPackage->id,
            'type'                   => 'customization_approved',
            'url'                    => $url,
        ];
    }

    /**
     * (Optional) Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $role = $this->role ?: ($notifiable->role ?? null);
        $url = $this->bookingUrlFor($role);

        $message = strip_tags($this->customMessage)
            ?: "Your customization request for booking #{$this->booking->id} has been approved by the funeral parlor.";

        return (new MailMessage)
            ->subject('Customization Request Approved')
            ->greeting('Hello!')
            ->line($message)
            ->action('View Booking', $url)
            ->line('You may now proceed with the next steps of your booking.')
->salutation('Regards,<br>EternaLink');
    }

    /**
     * Helper: Choose correct URL for client/agent
     */
    protected function bookingUrlFor($role)
    {
        if ($role === 'agent') {
            return route('agent.bookings.show', $this->booking->id);
        }
        // Default to client
        return route('client.bookings.show', $this->booking->id);
    }
}
