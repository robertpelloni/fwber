<?php

namespace App\Notifications;

use App\Models\Gift;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Notifications\Traits\ChecksNotificationPreferences;

class GiftReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $sender;
    public $gift;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, Gift $gift, ?string $message = null)
    {
        $this->sender = $sender;
        $this->gift = $gift;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Use 'gift_received' preference key, default to database and push
        // Note: You might need to add 'gift_received' to the valid types in ChecksNotificationPreferences if it validates strictly
        // For now assuming it falls back to defaults if not found or we can use a generic type
        return $this->getChannels($notifiable, 'gift_received', ['database', WebPushChannel::class]);
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $body = "You received a {$this->gift->name}!";
        if ($this->message) {
            $body .= " \"{$this->message}\"";
        }

        return (new WebPushMessage)
            ->title('New Gift from ' . $this->sender->name)
            ->body($body)
            ->action('View Gifts', 'view_gifts')
            ->data(['url' => '/profile']); // Or wherever gifts are viewed
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gift_received',
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'gift_id' => $this->gift->id,
            'gift_name' => $this->gift->name,
            'gift_icon' => $this->gift->icon_url,
            'message' => $this->message,
        ];
    }
}
