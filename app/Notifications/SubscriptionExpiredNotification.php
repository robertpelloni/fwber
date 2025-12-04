<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Notifications\Traits\ChecksNotificationPreferences;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $subscription;

    /**
     * Create a new notification instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannels($notifiable, 'subscription_expired');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Subscription Has Expired')
            ->line('Your premium subscription has expired.')
            ->line('You have been reverted to the free tier.')
            ->action('Renew Subscription', url('/subscription'))
            ->line('Thank you for being a valued member!');
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Subscription Expired')
            ->body('Your premium subscription has expired. Renew now to keep your benefits.')
            ->action('Renew', 'renew_subscription')
            ->data(['url' => '/subscription']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'message' => 'Your subscription has expired. Renew now to regain premium features.',
        ];
    }
}
