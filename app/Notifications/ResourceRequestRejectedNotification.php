<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ResourceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResourceRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $resourceRequest;
    public $forProvider;

    public function __construct(ResourceRequest $resourceRequest, $forProvider = false)
    {
        $this->resourceRequest = $resourceRequest->load(['provider', 'requester', 'providerItem', 'requestedItem']);
        $this->forProvider = $forProvider;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toArray($notifiable)
    {
        $item = $this->resourceRequest->providerItem->name ?? 'an item';
        if ($this->forProvider) {
            return [
                'title' => 'Request Rejected',
                'message' => "You rejected the request for $item from {$this->resourceRequest->requester->name}.",
                'resource_request_id' => $this->resourceRequest->id,
                'type' => 'resource_request',
            ];
        } else {
            return [
                'title' => 'Request Rejected',
                'message' => "Your request for $item was rejected by {$this->resourceRequest->provider->name}.",
                'resource_request_id' => $this->resourceRequest->id,
                'type' => 'resource_request',
            ];
        }
    }

    public function toMail($notifiable)
    {
        $item = $this->resourceRequest->providerItem->name ?? 'an item';
        $url = route('funeral.partnerships.resource_requests.show', $this->resourceRequest->id);
        if ($this->forProvider) {
            return (new MailMessage)
                ->subject('Request Rejected')
                ->line("You rejected the request for $item from {$this->resourceRequest->requester->name}.")
                ->action('View Request', $url);
        } else {
            return (new MailMessage)
                ->subject('Your Request was Rejected')
                ->line("Your request for $item was rejected by {$this->resourceRequest->provider->name}.")
                ->action('View Request', $url)
                ->salutation('Regards,<br>EternaLink');
        }
    }
}
