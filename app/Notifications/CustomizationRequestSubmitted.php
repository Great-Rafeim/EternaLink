<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\CustomizedPackage;

class CustomizationRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $customizedPackage;
    public $customMessage;
    public $role;

    /**
     * Create a new notification instance.
     *
     * @param CustomizedPackage $customizedPackage
     * @param string|null $customMessage
     * @param string|null $role
     */
    public function __construct(CustomizedPackage $customizedPackage, $customMessage = null, $role = null)
    {
        $this->customizedPackage = $customizedPackage;
        $this->customMessage = $customMessage;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // Add 'mail' if you want to email as well
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        // If a custom message is given, use it. Otherwise, fallback per role.
        $defaultMessage = 'A package customization request has been sent.';

        if ($this->customMessage) {
            $message = $this->customMessage;
        } elseif ($this->role === 'client') {
            $message = 'Your customization request has been submitted and is awaiting review by the funeral parlor.';
        } elseif ($this->role === 'funeral') {
            $message = 'A new customization request was submitted by an agent and is awaiting your review.';
        } else {
            $message = $defaultMessage;
        }

        return [
            'message' => $message,
            'customized_package_id' => $this->customizedPackage->id,
            'booking_id'           => $this->customizedPackage->booking_id,
            'role'                 => $this->role,
            'type'                 => 'customization_submitted',
        ];
    }
}
