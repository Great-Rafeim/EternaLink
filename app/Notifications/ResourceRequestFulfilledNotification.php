<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ResourceRequest;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ResourceRequestFulfilledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $resourceRequest;
    public $forProvider; // true = notify provider, false = notify requester

    /**
     * Create a new notification instance.
     */
    public function __construct(ResourceRequest $resourceRequest, $forProvider = false)
    {
        $this->resourceRequest = $resourceRequest;
        $this->forProvider = $forProvider;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Send via both mail and database (notification bell)
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $isAsset = $this->resourceRequest->providerItem
            && $this->resourceRequest->providerItem->category
            && $this->resourceRequest->providerItem->category->is_asset;

        $requester = $this->resourceRequest->requester->name ?? 'Unknown';
        $provider = $this->resourceRequest->provider->name ?? 'Unknown';
        $itemName = $this->resourceRequest->providerItem->name ?? 'Unknown';
        $quantity = $this->resourceRequest->quantity ?? 1;
        $status = ucfirst($this->resourceRequest->status);

        if ($this->forProvider) {
            $intro = "Your resource (\"$itemName\") request **to $requester** has been fulfilled by the borrower.";
        } else {
            $intro = "Your request to borrow \"$itemName\" from $provider has been fulfilled. The item is now available in your inventory.";
        }

        $mail = (new MailMessage)
            ->subject("Resource Request Fulfilled: $itemName")
            ->greeting('Hello!')
            ->line($intro)
            ->line("**Item:** $itemName")
            ->line("**Quantity:** $quantity")
            ->line("**Status:** $status");

        if ($isAsset) {
            $mail->line("You have borrowed this as an asset. Please be reminded of the return date.");
        } else {
            $mail->line("Consumable stocks have been transferred.");
        }

        $mail->action('View Request', url('/funeral/resource-requests/' . $this->resourceRequest->id))
            ->line('Thank you for using EternaLink!');

        return $mail;
    }

    /**
     * Get the array representation of the notification (for database/bell icon).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        $isAsset = $this->resourceRequest->providerItem
            && $this->resourceRequest->providerItem->category
            && $this->resourceRequest->providerItem->category->is_asset;

        $requester = $this->resourceRequest->requester->name ?? 'Unknown';
        $provider = $this->resourceRequest->provider->name ?? 'Unknown';
        $itemName = $this->resourceRequest->providerItem->name ?? 'Unknown';

        return [
            'title' => 'Resource Request Fulfilled',
            'message' => $this->forProvider
                ? "Your resource \"$itemName\" was marked as fulfilled by $requester."
                : "Your borrowed item \"$itemName\" from $provider is now in your inventory.",
            'item_id' => $this->resourceRequest->provider_item_id,
            'resource_request_id' => $this->resourceRequest->id,
            'is_asset' => $isAsset,
            'action_url' => url('/funeral/resource-requests/' . $this->resourceRequest->id),
        ];
    }
}
