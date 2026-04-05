<?php

namespace App\Notifications;

use App\Models\Gift;
use App\Models\User;
use App\Notifications\Traits\ChecksNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\ExpoPushNotifications\ExpoChannel;
use NotificationChannels\ExpoPushNotifications\ExpoMessage;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class GiftReceivedNotification extends Notification implements ShouldQueue
{
    use ChecksNotificationPreferences, Queueable;

    public function __construct(
        public User $sender,
        public Gift $gift,
        public ?string $message = null,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->getChannels($notifiable, 'gift_received', ['database', WebPushChannel::class, ExpoChannel::class]);
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Gift received')
            ->body("{$this->sender->name} sent you {$this->gift->name}.")
            ->action('Open Wallet', 'open_wallet')
            ->data($this->toArray($notifiable));
    }

    public function toExpoPush($notifiable)
    {
        return (new ExpoMessage())
            ->title('Gift received')
            ->body("{$this->sender->name} sent you {$this->gift->name}.")
            ->data($this->toArray($notifiable))
            ->priority('high');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gift',
            'title' => 'Gift received',
            'body' => "{$this->sender->name} sent you {$this->gift->name}.",
            'message' => $this->message ?: "{$this->sender->name} sent you {$this->gift->name}.",
            'url' => '/wallet?tab=gifts',
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'gift_id' => $this->gift->id,
            'gift_name' => $this->gift->name,
        ];
    }
}
