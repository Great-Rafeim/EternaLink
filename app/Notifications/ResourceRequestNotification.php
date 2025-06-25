<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ResourceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResourceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $resourceRequestId;
    protected $isProvider;
    protected $type;

    public function __construct($resourceRequestId, $isProvider = false, $type = 'submitted')
    {
        $this->resourceRequestId = $resourceRequestId;
        $this->isProvider = $isProvider;
        $this->type = $type;
    }

    protected function getResourceRequest()
    {
        return ResourceRequest::with(['provider', 'requester', 'providerItem', 'requestedItem'])->find($this->resourceRequestId);
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable)
    {
        $request = $this->getResourceRequest();

        $providerName = $request->provider->name ?? 'a partner';
        $requesterName = $request->requester->name ?? 'a partner';
        $item = $request->providerItem->name ?? 'an item';

        if ($this->isProvider) {
            switch ($this->type) {
                case 'approved':
                    $title = 'Request Approved';
                    $message = "You have approved a resource request from $requesterName for $item.";
                    break;
                case 'rejected':
                    $title = 'Request Rejected';
                    $message = "You have rejected a resource request from $requesterName for $item.";
                    break;
                case 'cancelled':
                    $title = 'Request Cancelled';
                    $message = "The resource request from $requesterName for $item was cancelled by the requester.";
                    break;
                default: // submitted
                    $title = 'New Resource Request';
                    $message = "You received a new resource request from $requesterName for $item.";
            }
        } else {
            switch ($this->type) {
                case 'approved':
                    $title = 'Request Approved';
                    $message = "Your request for $item has been approved by $providerName.";
                    break;
                case 'rejected':
                    $title = 'Request Rejected';
                    $message = "Your request for $item was rejected by $providerName.";
                    break;
                case 'cancelled':
                    $title = 'Request Cancelled';
                    $message = "You have cancelled your resource request for $item.";
                    break;
                default: // submitted
                    $title = 'Resource Request Submitted';
                    $message = "Your resource request for $item has been submitted to $providerName.";
            }
        }

        return [
            'title' => $title,
            'message' => $message,
            'resource_request_id' => $request->id,
            'type' => 'resource_request',
        ];
    }

    public function toMail($notifiable)
    {
        $request = $this->getResourceRequest();
        $url = route('funeral.partnerships.resource_requests.show', $request->id);

        $providerName = $request->provider->name ?? 'a partner';
        $requesterName = $request->requester->name ?? 'a partner';
        $item = $request->providerItem->name ?? 'an item';

        if ($this->isProvider) {
            switch ($this->type) {
                case 'approved':
                    $subject = 'You Approved a Resource Request';
                    $line = "You have approved a resource request from $requesterName for $item.";
                    break;
                case 'rejected':
                    $subject = 'You Rejected a Resource Request';
                    $line = "You have rejected a resource request from $requesterName for $item.";
                    break;
                case 'cancelled':
                    $subject = 'Request Cancelled';
                    $line = "The resource request from $requesterName for $item was cancelled by the requester.";
                    break;
                default: // submitted
                    $subject = 'New Resource Request';
                    $line = "You received a new resource request from $requesterName for $item.";
            }
        } else {
            switch ($this->type) {
                case 'approved':
                    $subject = 'Your Request was Approved';
                    $line = "Your request for $item has been approved by $providerName.";
                    break;
                case 'rejected':
                    $subject = 'Your Request was Rejected';
                    $line = "Your request for $item was rejected by $providerName.";
                    break;
                case 'cancelled':
                    $subject = 'Request Cancelled';
                    $line = "You have cancelled your resource request for $item.";
                    break;
                default: // submitted
                    $subject = 'Resource Request Submitted';
                    $line = "Your resource request for $item has been submitted to $providerName.";
            }
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($line)
            ->action('View Request', $url)
            ->line('Thank you for using EternaLink!');
    }
}
