<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('schema:fix-missing', function () {
    $this->info('Checking for missing tables...');
    
    $schema = \Illuminate\Support\Facades\Schema::connection(null);
    
    // Fix missing photo_unlocks table
    if (!$schema->hasTable('photo_unlocks')) {
        $this->info('Creating photo_unlocks table...');
        $schema->create('photo_unlocks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->decimal('cost', 8, 2);
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'photo_id']);
        });
        $this->info('Created photo_unlocks table.');
    } else {
        $this->info('photo_unlocks table already exists.');
    }

    // Fix missing unlock_price column
    if ($schema->hasTable('photos') && !$schema->hasColumn('photos', 'unlock_price')) {
        $this->info('Adding unlock_price to photos table...');
        $schema->table('photos', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->decimal('unlock_price', 8, 2)->nullable()->after('is_private');
        });
        $this->info('Added unlock_price column.');
    } else {
        $this->info('unlock_price column already exists.');
    }
    
    // Fix missing content_unlocks table
    if (!$schema->hasTable('content_unlocks')) {
        $this->info('Creating content_unlocks table...');
            $schema->create('content_unlocks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('content_type');
            $table->string('content_id');
            $table->decimal('cost', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'content_type', 'content_id']);
        });
        $this->info('Created content_unlocks table.');
    } else {
        $this->info('content_unlocks table already exists.');
    }

    $this->info('Schema fix completed.');
})->purpose('Fix missing database tables and columns manually');

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

// Analyze slow requests for performance alerts every hour
use App\Jobs\AnalyzeSlowRequests;
Schedule::job(new AnalyzeSlowRequests)->hourly()
    ->name('analyze-slow-requests')
    ->withoutOverlapping();

// Cache warming
Schedule::command('cache:warm')->everyFiveMinutes()
    ->name('warm-cache')
    ->withoutOverlapping();
