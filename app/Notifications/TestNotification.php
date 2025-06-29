<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['database', 'mail']; // Add 'mail' if you want to test mail
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'This is a test notification sent directly!',
            'test'    => true,
        ];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Test Notification')
            ->line('This is a test notification sent directly to your email!')
            ->action('View Dashboard', url('/'));
    }
}
