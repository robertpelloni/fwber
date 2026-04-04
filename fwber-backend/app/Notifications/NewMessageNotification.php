<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Traits\ChecksNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\ExpoPushNotifications\ExpoChannel;
use NotificationChannels\ExpoPushNotifications\ExpoMessage;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use ChecksNotificationPreferences, Queueable;

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
        return $this->getChannels($notifiable, 'new_message', ['database', WebPushChannel::class, ExpoChannel::class]);
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Message from '.$this->sender->name)
            ->body('You have a new encrypted message.')
            ->action('Reply', 'reply_message')
            ->data([
                'url' => '/messages?user='.$this->sender->id,
                'type' => 'message',
                'user_id' => $this->sender->id,
                'user_name' => $this->sender->name,
            ]);
    }

    /**
     * Get the Expo representation of the notification.
     */
    public function toExpoPush($notifiable)
    {
        return (new ExpoMessage())
            ->title('New Message from '.$this->sender->name)
            ->body('You have a new encrypted message.')
            ->data([
                'url' => '/messages?user='.$this->sender->id,
                'type' => 'message',
                'user_id' => $this->sender->id,
                'user_name' => $this->sender->name,
            ])
            ->priority('high');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'message',
            'title' => 'New Message from '.$this->sender->name,
            'body' => $this->messageContent,
            'message' => $this->messageContent,
            'url' => '/messages?user='.$this->sender->id,
            'user_id' => $this->sender->id,
            'user_name' => $this->sender->name,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'chatroom_id' => $this->chatroomId,
        ];
    }
}
