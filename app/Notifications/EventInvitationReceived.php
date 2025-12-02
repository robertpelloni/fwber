<?php

namespace App\Notifications;

use App\Models\EventInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class EventInvitationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(EventInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $inviterName = $this->invitation->inviter->name;
        $eventName = $this->invitation->event->title;

        return (new WebPushMessage)
            ->title('New Event Invitation')
            ->body("{$inviterName} invited you to {$eventName}")
            ->action('View Invitation', 'view_invitation')
            ->data(['url' => '/events']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'event_id' => $this->invitation->event_id,
            'inviter_id' => $this->invitation->inviter_id,
            'message' => "{$this->invitation->inviter->name} invited you to {$this->invitation->event->title}",
        ];
    }
}
