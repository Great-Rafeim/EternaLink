<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\AssetReservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AssetReservationStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $reservation;
    public $action;
    public $role; // provider, requester, admin

    /**
     * Create a new notification instance.
     */
    public function __construct(AssetReservation $reservation, $action, $role = null)
    {
        $this->reservation = $reservation;
        $this->action = $action;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // You can add 'mail' here as well if you want email notifications
    }

    /**
     * Get the array representation of the notification (database).
     */
    public function toDatabase($notifiable)
    {
        $item = $this->reservation->inventoryItem;
        $category = optional($item)->category;
        $provider = $this->reservation->creator;
        $requester = $this->reservation->sharedWithPartner ?: optional($this->reservation->booking)->client;

        // Notification titles/messages per action
        $actionMessages = [
            'reserved'  => 'Reservation created.',
            'in_use'    => 'Asset is now in use.',
            'completed' => 'Asset returned.',
            'cancelled' => 'Reservation cancelled.',
            'received'  => 'Asset receipt acknowledged by provider.',
            'available' => 'Asset is available again.'
        ];

        $message = $actionMessages[$this->action] ?? 'Status updated.';

        return [
            'reservation_id' => $this->reservation->id,
            'asset_name'     => $item->name ?? '',
            'category'       => $category->name ?? '',
            'status'         => $this->reservation->status,
            'action'         => $this->action,
            'by_role'        => $this->role,
            'provider_name'  => $provider ? $provider->name : null,
            'requester_name' => $requester ? $requester->name : null,
            'message'        => $message,
            'timestamp'      => now()->toDateTimeString(),
            'url'            => route('funeral.assets.reservations.index')
        ];
    }

    /**
     * (Optional) Get the mail representation of the notification (if you add 'mail' to via).
     */
    public function toMail($notifiable)
    {
        $item = $this->reservation->inventoryItem;
        $message = "Asset Reservation Update: {$item->name} is now {$this->action}.";

        return (new MailMessage)
            ->subject('Asset Reservation Update')
            ->greeting('Hello!')
            ->line($message)
            ->action('View Reservations', route('funeral.assets.reservations.index'))
            ->line('Thank you for using our system!');
    }
}
