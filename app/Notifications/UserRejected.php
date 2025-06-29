<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Registration is Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We regret to inform you that your registration at EternaLink has been rejected.')
            ->line('If you believe this was a mistake, please contact our support team.')
->salutation('Regards,<br>EternaLink');
    }

    public function toArray($notifiable)
    {
        return [
            'title'   => 'Registration Rejected',
            'message' => 'Your registration has been rejected. Please contact support if you have questions.',
        ];
    }
}
