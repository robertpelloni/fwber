<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerformanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $slowEndpoints;
    protected string $period;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $slowEndpoints, string $period = '1 hour')
    {
        $this->slowEndpoints = $slowEndpoints;
        $this->period = $period;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('⚠️ Performance Alert: Slow Endpoints Detected')
            ->greeting('Hello Admin,')
            ->line("The following endpoints have exceeded performance thresholds in the last {$this->period}:");

        foreach ($this->slowEndpoints as $endpoint) {
            $message->line("- **{$endpoint['method']} {$endpoint['route']}**")
                ->line("  Avg Duration: {$endpoint['avg_duration']}ms")
                ->line("  Occurrences: {$endpoint['count']}")
                ->line("  Max Duration: {$endpoint['max_duration']}ms");
        }

        return $message
            ->action('View Analytics', url('/admin/analytics'))
            ->line('Please investigate these endpoints to ensure optimal performance.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'performance_alert',
            'period' => $this->period,
            'endpoints' => $this->slowEndpoints,
            'alerted_at' => now()->toIso8601String(),
        ];
    }
}
