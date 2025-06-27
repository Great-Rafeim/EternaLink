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

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Booking $booking, CustomizedPackage $customizedPackage)
    {
        $this->booking = $booking;
        $this->customizedPackage = $customizedPackage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Use 'mail' if you want to send email as well.
        return ['database'];
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Customization Approved',
            'message' => "Your customization request for Booking #{$this->booking->id} has been approved by the funeral parlor.",
            'booking_id' => $this->booking->id,
            'customized_package_id' => $this->customizedPackage->id,
            'type' => 'customization_approved',
        ];
    }

    /**
     * (Optional) Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Customization Request Approved')
            ->greeting('Hello!')
            ->line("Customization request for Booking #{$this->booking->id} has been approved by the funeral parlor.")
            ->action('View Booking', url(route('client.bookings.show', $this->booking->id)))
            ->line('You may now proceed with the next steps of your booking.');
    }
}
