<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\CemeteryBooking;

class CemeteryBookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $cemeteryBooking;
    protected $customMessage;
    protected $role;

    public function __construct(CemeteryBooking $cemeteryBooking, $customMessage = null, $role = null)
    {
        $this->cemeteryBooking = $cemeteryBooking;
        $this->customMessage = $customMessage;
        $this->role = $role;

        \Log::debug('[NOTIFY] CemeteryBookingStatusChanged::__construct', [
            'cemetery_booking_id' => $cemeteryBooking->id,
            'customMessage'       => $customMessage,
            'role'                => $role,
        ]);
    }

    public function via($notifiable)
    {
        \Log::debug('[NOTIFY] CemeteryBookingStatusChanged::via', [
            'notifiable_id' => $notifiable->id ?? null,
            'notifiable_role' => $notifiable->role ?? null,
        ]);
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $status = ucfirst($this->cemeteryBooking->status);
        $role = $this->role ?? ($notifiable->role ?? null);

        $url = $this->bookingUrlFor($role);

        $message = $this->customMessage
            ?: "Your cemetery booking is now <b>{$status}</b>.";

        \Log::debug('[NOTIFY] CemeteryBookingStatusChanged::toDatabase', [
            'notifiable_id'    => $notifiable->id ?? null,
            'notifiable_role'  => $role,
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'booking_id'       => $this->cemeteryBooking->booking_id,
            'status'           => $status,
            'url'              => $url,
            'message'          => $message,
        ]);

        return [
            'message'      => $message,
            'booking_id'   => $this->cemeteryBooking->booking_id,
            'status'       => $status,
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'url'          => $url,
            'role'         => $role,
        ];
    }

    protected function bookingUrlFor($role)
    {
        \Log::debug('[NOTIFY] CemeteryBookingStatusChanged::bookingUrlFor', [
            'role' => $role,
            'cemetery_booking_id' => $this->cemeteryBooking->id,
            'booking_id' => $this->cemeteryBooking->booking_id,
        ]);

        switch ($role) {
            case 'funeral':
                return route('funeral.bookings.show', $this->cemeteryBooking->booking_id);
            case 'agent':
                return route('agent.bookings.show', $this->cemeteryBooking->booking_id);
            case 'client':
            default:
                return route('client.bookings.show', $this->cemeteryBooking->booking_id);
        }
    }
}
