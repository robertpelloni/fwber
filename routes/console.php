<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Model and token maintenance
Schedule::command('model:prune')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Background jobs for premium features
use App\Jobs\ExpireBoosts;
use App\Jobs\SendEventReminders;
use App\Jobs\CleanupExpiredSubscriptions;

// Expire boosts every 15 minutes
Schedule::job(new ExpireBoosts)->everyFifteenMinutes()
    ->name('expire-boosts')
    ->withoutOverlapping();

// Send event reminders every hour
Schedule::job(new SendEventReminders)->hourly()
    ->name('send-event-reminders')
    ->withoutOverlapping();

// Cleanup expired subscriptions daily at 2 AM
Schedule::job(new CleanupExpiredSubscriptions)->dailyAt('02:00')
    ->name('cleanup-expired-subscriptions')
    ->withoutOverlapping();
