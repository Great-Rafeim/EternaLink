<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CemeteryBookingRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public $notifData;

    /**
     * Create a new notification instance.
     *
     * @param array $notifData
     */
    public function __construct(array $notifData)
    {
        $this->notifData = $notifData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification (database).
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title'         => $this->notifData['title']         ?? 'Cemetery Booking Rejected',
            'message'       => $this->notifData['message']       ?? 'Your cemetery booking has been rejected.',
            'booking_id'    => $this->notifData['booking_id']    ?? null,
            'plot_number'   => $this->notifData['plot_number']   ?? null,
            'client_name'   => $this->notifData['client_name']   ?? null,
            'cemetery_name' => $this->notifData['cemetery_name'] ?? null,
            'role'          => $this->notifData['role']          ?? null,
            'url'           => isset($this->notifData['role'], $this->notifData['booking_id'])
                ? $this->routeUrl($this->notifData['role'], $this->notifData['booking_id'])
                : null,
        ];
    }

    /**
     * Generate the route for booking show based on role.
     *
     * @param string $role
     * @param int $bookingId
     * @return string|null
     */
    protected function routeUrl($role, $bookingId)
    {
        switch ($role) {
            case 'agent':
                return route('agent.bookings.show', $bookingId);
            case 'funeral':
                return route('funeral.bookings.show', $bookingId);
            case 'client':
            default:
                return route('client.bookings.show', $bookingId);
        }
    }
}
