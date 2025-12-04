<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Notifications\Traits\ChecksNotificationPreferences;

use Illuminate\Notifications\Messages\MailMessage;

class PushMessage extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $title;
    public $body;
    public $url;
    public $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $body, ?string $url = null, string $type = 'marketing')
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Use preference based on type
        $channels = $this->getChannels($notifiable, $this->type);
        
        // Ensure broadcast is included for real-time updates
        if (!in_array('broadcast', $channels)) {
             $channels[] = 'broadcast';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('View Details', url($this->url ?? '/'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ]);
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\WebPush\WebPushMessage
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->action('View', 'view_app')
            ->data(['url' => $this->url ?? '/']);
    }
}
