<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use App\Notifications\Traits\ChecksNotificationPreferences;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable, ChecksNotificationPreferences;

    public $amount;
    public $currency;

    /**
     * Create a new notification instance.
     */
    public function __construct($amount, $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->getChannels($notifiable, 'payment_failed');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $formattedAmount = number_format($this->amount, 2) . ' ' . strtoupper($this->currency);

        return (new MailMessage)
            ->subject('Payment Failed')
            ->line('We were unable to process your payment of ' . $formattedAmount . '.')
            ->line('Please update your payment method to avoid service interruption.')
            ->action('Update Payment Method', url('/settings/billing'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $formattedAmount = number_format($this->amount, 2) . ' ' . strtoupper($this->currency);

        return (new WebPushMessage)
            ->title('Payment Failed')
            ->body("We couldn't process your payment of {$formattedAmount}. Please update your billing info.")
            ->action('Update Billing', 'update_billing')
            ->data(['url' => '/settings/billing']);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => 'Payment of ' . number_format($this->amount, 2) . ' ' . strtoupper($this->currency) . ' failed.',
        ];
    }
}
