<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class PendingBusinessRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    public $user; // The new user/applicant

    /**
     * Create a new notification instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $role = Str::headline($this->user->role);

        return (new MailMessage)
            ->subject("New $role Registration Pending Approval")
            ->greeting("Hello Admin,")
            ->line("A new {$role} account has registered and requires your approval.")
            ->line("Applicant Name: {$this->user->name}")
            ->line("Applicant Email: {$this->user->email}")
            ->action('Review Pending Registrations', url('/admin/pending-registrations')) // Update to your admin route
            ->line('Please log in to review and approve or reject this registration.')
->salutation('Regards,<br>EternaLink');
    }

    /**
     * Get the array representation of the notification for database.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title'     => 'New Business Registration Pending Approval',
            'message'   => "A new " . Str::headline($this->user->role) . " account has been registered and needs your approval.",
            'user_id'   => $this->user->id,
            'user_name' => $this->user->name,
            'user_email'=> $this->user->email,
            'user_role' => $this->user->role,
        ];
    }
}
