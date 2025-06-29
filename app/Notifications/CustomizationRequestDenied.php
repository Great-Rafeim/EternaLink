<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Booking;
use App\Models\CustomizedPackage;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomizationRequestDenied extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $customized;
    public $customMessage;
    public $role;

    /**
     * Accepts optional custom message and role (e.g., 'client' or 'agent').
     */
    public function __construct(Booking $booking, CustomizedPackage $customized, $customMessage = null, $role = null)
    {
        $this->booking = $booking;
        $this->customized = $customized;
        $this->customMessage = $customMessage;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable)
    {
        return [
            'title'      => 'Customization Request Denied',
            'message'    => $this->customMessage
                ?: "Your customization request for Booking #{$this->booking->id} was denied by the funeral parlor.",
            'booking_id' => $this->booking->id,
            'customized_package_id' => $this->customized->id,
            'role'       => $this->role,
            'type'       => 'customization_denied',
        ];
    }
}
