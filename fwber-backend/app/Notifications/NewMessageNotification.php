<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Notifications\Traits\ChecksNotificationPreferences;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $sender;
    public $messageContent;
    public $chatroomId;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, string $messageContent, int $chatroomId)
    {
        $this->sender = $sender;
        $this->messageContent = $messageContent;
        $this->chatroomId = $chatroomId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Default to database and push only for messages
        return $this->getChannels($notifiable, 'new_message', ['database', WebPushChannel::class]);
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Message from ' . $this->sender->name)
            ->body($this->messageContent)
            ->action('Reply', 'reply_message')
            ->data(['url' => '/chatrooms/' . $this->chatroomId]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'message' => $this->messageContent,
            'chatroom_id' => $this->chatroomId,
        ];
    }
}
