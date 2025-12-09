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

class NewMatchNotification extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $matchedUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $matchedUser)
    {
        $this->matchedUser = $matchedUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannels($notifiable, 'new_match');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have a new match!')
            ->line('You matched with ' . $this->matchedUser->name . '!')
            ->action('View Match', url('/matches'))
            ->line('Send them a message now!');
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Match!')
            ->body("You matched with {$this->matchedUser->name}!")
            ->action('View Match', 'view_match')
            ->data(['url' => '/matches']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'matched_user_id' => $this->matchedUser->id,
            'matched_user_name' => $this->matchedUser->name,
            'message' => 'You matched with ' . $this->matchedUser->name . '!',
        ];
    }
}
