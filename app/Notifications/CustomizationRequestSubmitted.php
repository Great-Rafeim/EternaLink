<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Notifications\CustomizationRequestSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;


class CustomizationRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $customizedPackage;

    public function __construct($customizedPackage)
    {
        $this->customizedPackage = $customizedPackage;
    }

    public function via($notifiable)
    {
        return ['database']; // or ['mail', 'database'] if you want to send an email as well
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'A client has sent a customization request for a package.',
            'customized_package_id' => $this->customizedPackage->id,
            'booking_id' => $this->customizedPackage->booking_id,
            // Add more details if needed
        ];
    }
}
