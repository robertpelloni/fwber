<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Traits\ChecksNotificationPreferences;

class GroupMatchRequestNotification extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $sourceGroup;
    public $targetGroup;
    public $initiator;

    /**
     * Create a new notification instance.
     */
    public function __construct(Group $sourceGroup, Group $targetGroup, User $initiator)
    {
        $this->sourceGroup = $sourceGroup;
        $this->targetGroup = $targetGroup;
        $this->initiator = $initiator;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Use 'new_match' preference for now, or create a 'group_match' one later
        return $this->getChannels($notifiable, 'new_match');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Group Match Request!')
            ->line("The group '{$this->sourceGroup->name}' wants to connect with your group '{$this->targetGroup->name}'.")
            ->line("Request initiated by {$this->initiator->name}.")
            ->action('View Requests', url("/groups/{$this->targetGroup->id}/requests"))
            ->line('Go to the requests page to accept or reject.');
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Group Match Request')
            ->body("'{$this->sourceGroup->name}' wants to connect with '{$this->targetGroup->name}'")
            ->action('View', 'view_group_requests')
            ->data(['url' => "/groups/{$this->targetGroup->id}/requests"]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_match_request',
            'source_group_id' => $this->sourceGroup->id,
            'source_group_name' => $this->sourceGroup->name,
            'target_group_id' => $this->targetGroup->id,
            'target_group_name' => $this->targetGroup->name,
            'initiator_name' => $this->initiator->name,
            'message' => "The group '{$this->sourceGroup->name}' wants to connect with '{$this->targetGroup->name}'.",
        ];
    }
}
