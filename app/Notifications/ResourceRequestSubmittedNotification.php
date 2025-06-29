<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ResourceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResourceRequestSubmittedNotification extends Notification implements ShouldQueue
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
                'title' => 'New Resource Request',
                'message' => "You received a new resource request for $item from {$this->resourceRequest->requester->name}.",
                'resource_request_id' => $this->resourceRequest->id,
                'type' => 'resource_request',
            ];
        } else {
            return [
                'title' => 'Resource Request Submitted',
                'message' => "Your resource request for $item has been submitted to {$this->resourceRequest->provider->name}.",
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
                ->subject('New Resource Request')
                ->line("You received a new resource request for $item from {$this->resourceRequest->requester->name}.")
                ->action('View Request', $url)
                ->line('Please review and respond to the request.')
->salutation('Regards,<br>EternaLink');
        } else {
            return (new MailMessage)
                ->subject('Resource Request Submitted')
                ->line("Your resource request for $item has been submitted to {$this->resourceRequest->provider->name}.")
                ->action('View Your Request', $url)
                ->line('You will be notified once it is processed.')
->salutation('Regards,<br>EternaLink');
        }
    }
}
