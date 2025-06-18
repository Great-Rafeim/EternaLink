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

    public function __construct(Booking $booking, CustomizedPackage $customized)
    {
        $this->booking = $booking;
        $this->customized = $customized;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Customization Request Denied',
            'message' => "Your customization request for Booking #{$this->booking->id} was denied by the funeral parlor.",
            'booking_id' => $this->booking->id,
            'customized_package_id' => $this->customized->id,
            'type' => 'customization_denied',
        ];
    }
}
