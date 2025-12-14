<?php

namespace App\Jobs;

use App\Models\SlowRequest;
use App\Models\User;
use App\Notifications\PerformanceAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AnalyzeSlowRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!config('apm.enabled', false)) {
            return;
        }

        $threshold = config('apm.alert_threshold_ms', 2000);
        $minOccurrences = config('apm.alert_min_occurrences', 5);
        $adminEmail = config('apm.alert_email');

        // Analyze requests from the last hour
        $startTime = now()->subHour();

        $slowEndpoints = SlowRequest::where('created_at', '>=', $startTime)
            ->select(
                'method',
                'route_name',
                'url',
                DB::raw('AVG(duration_ms) as avg_duration'),
                DB::raw('MAX(duration_ms) as max_duration'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('method', 'route_name', 'url')
            ->having('avg_duration', '>', $threshold)
            ->having('count', '>=', $minOccurrences)
            ->get();

        if ($slowEndpoints->isEmpty()) {
            return;
        }

        $formattedEndpoints = $slowEndpoints->map(function ($item) {
            return [
                'method' => $item->method,
                'route' => $item->route_name ?: $item->url,
                'avg_duration' => round($item->avg_duration, 2),
                'max_duration' => round($item->max_duration, 2),
                'count' => $item->count,
            ];
        })->toArray();

        Log::warning('Performance Alert: Slow endpoints detected', ['endpoints' => $formattedEndpoints]);

        // Notify Admins
        // In a real app, you might have a specific Admin model or role.
        // Here we'll notify the first admin user or a configured email.
        
        $admin = User::where('email', $adminEmail)->first();
        
        if ($admin) {
            $admin->notify(new PerformanceAlertNotification($formattedEndpoints));
        } else {
            // Fallback: Log that we couldn't find the admin to notify
            Log::error("Could not find admin user with email {$adminEmail} to send performance alert.");
        }
    }
}
