<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiredNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CleanupExpiredSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'default';

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Find and revoke premium access for users with expired subscriptions.
     */
    public function handle(): void
    {
        $now = Carbon::now();

        // Find subscriptions that have ended and are still marked as active
        // Note: stripe_status might be 'active' or 'trialing'
        $expiredSubscriptions = Subscription::whereIn('stripe_status', ['active', 'trialing'])
            ->where('ends_at', '<=', $now)
            ->with('user')
            ->get();

        $count = $expiredSubscriptions->count();

        if ($count === 0) {
            Log::info('CleanupExpiredSubscriptions: No expired subscriptions to process');
            return;
        }

        foreach ($expiredSubscriptions as $subscription) {
            $user = $subscription->user;
            
            if (!$user) {
                Log::warning("CleanupExpiredSubscriptions: User not found for subscription {$subscription->id}");
                continue;
            }

            // Update subscription status to expired
            $subscription->stripe_status = 'expired';
            $subscription->save();

            // Revoke premium access from user
            if ($user->tier === 'gold' || $user->tier === 'premium') {
                $user->tier = 'free';
                $user->tier_expires_at = null;
                $user->unlimited_swipes = false;
                $user->save();

                Log::info("CleanupExpiredSubscriptions: Revoked premium from user {$user->id}, subscription {$subscription->id}");

                // Invalidate user's subscription cache
                Cache::tags(['subscriptions', "user:{$user->id}"])->flush();

                // Send notification to user about subscription expiration
                try {
                    $user->notify(new SubscriptionExpiredNotification($subscription));
                } catch (\Exception $e) {
                    Log::error("CleanupExpiredSubscriptions: Failed to notify user {$user->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("CleanupExpiredSubscriptions: Processed {$count} expired subscription(s)");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupExpiredSubscriptions job failed: ' . $exception->getMessage());
    }
}
