<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApproved extends Notification implements ShouldQueue
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
            ->subject('Your Registration is Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Congratulations! Your registration at EternaLink has been approved.')
            ->action('Login Now', url(route('login')))
            ->line('Thank you for being part of EternaLink.')
->salutation('Regards,<br>EternaLink'); 
    }

    public function toArray($notifiable)
    {
        return [
            'title'   => 'Registration Approved',
            'message' => 'Your registration has been approved. You may now log in.',
            'url'     => route('login'),
        ];
    }
}
