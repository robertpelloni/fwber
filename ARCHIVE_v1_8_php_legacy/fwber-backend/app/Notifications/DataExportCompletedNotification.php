<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataExportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $downloadUrl;

    public function __construct($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Data Export is Ready')
            ->line('Your requested data export has been completed and is ready for download.')
            ->action('Download Data Export', $this->downloadUrl)
            ->line('This link will expire in 7 days for your security.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'data_export_completed',
            'download_url' => $this->downloadUrl,
            'message' => 'Your data export is ready to download.',
        ];
    }
}
