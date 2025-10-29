<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SecurityMonitoringService
{
    private array $config;
    private array $alertThresholds;

    public function __construct()
    {
        $this->config = config('security_monitoring', [
            'enabled' => true,
            'log_all_events' => false,
            'alert_threshold' => 100,
            'alert_channels' => ['log' => true],
        ]);

        $this->alertThresholds = [
            'failed_logins' => 5, // per 5 minutes
            'rate_limit_hits' => 10, // per hour
            'suspicious_activity' => 3, // per hour
            'content_moderation_flags' => 20, // per hour
            'device_fingerprint_alerts' => 5, // per hour
        ];
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $context): void
    {
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'event' => $event,
            'user_id' => $context['user_id'] ?? null,
            'ip_address' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'severity' => $context['severity'] ?? 'info',
            'context' => $context,
        ];

        // Log to structured logging system
        Log::channel('security')->info('Security Event', $logEntry);
        
        // Store in database for analysis
        $this->storeSecurityEvent($logEntry);
        
        // Check for suspicious patterns
        $this->analyzeSecurityPatterns($logEntry);
    }

    /**
     * Analyze security patterns
     */
    private function analyzeSecurityPatterns(array $logEntry): void
    {
        $patterns = [
            'multiple_failed_logins' => $this->checkMultipleFailedLogins($logEntry),
            'unusual_location_access' => $this->checkUnusualLocation($logEntry),
            'rapid_api_calls' => $this->checkRapidApiCalls($logEntry),
            'suspicious_content' => $this->checkSuspiciousContent($logEntry),
            'device_abuse' => $this->checkDeviceAbuse($logEntry),
        ];

        foreach ($patterns as $pattern => $detected) {
            if ($detected) {
                $this->triggerSecurityAlert($pattern, $logEntry);
            }
        }
    }

    /**
     * Check for multiple failed logins
     */
    private function checkMultipleFailedLogins(array $logEntry): bool
    {
        if ($logEntry['event'] !== 'auth_failed') {
            return false;
        }

        $userId = $logEntry['user_id'];
        $timeWindow = now()->subMinutes(5);
        
        $failedLogins = DB::table('security_events')
            ->where('event', 'auth_failed')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $timeWindow)
            ->count();

        return $failedLogins >= $this->alertThresholds['failed_logins'];
    }

    /**
     * Check for unusual location access
     */
    private function checkUnusualLocation(array $logEntry): bool
    {
        if ($logEntry['event'] !== 'location_update') {
            return false;
        }

        $userId = $logEntry['user_id'];
        $latitude = $logEntry['context']['latitude'] ?? null;
        $longitude = $logEntry['context']['longitude'] ?? null;

        if (!$latitude || !$longitude) {
            return false;
        }

        // Get user's last known location
        $lastLocation = DB::table('user_locations')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLocation) {
            return false;
        }

        // Calculate distance between locations
        $distance = $this->calculateDistance(
            $lastLocation->latitude,
            $lastLocation->longitude,
            $latitude,
            $longitude
        );

        // If distance is more than 1000km in less than 1 hour, it's suspicious
        $timeDiff = now()->diffInHours($lastLocation->created_at);
        return $distance > 1000 && $timeDiff < 1;
    }

    /**
     * Check for rapid API calls
     */
    private function checkRapidApiCalls(array $logEntry): bool
    {
        if ($logEntry['event'] !== 'api_call') {
            return false;
        }

        $userId = $logEntry['user_id'];
        $timeWindow = now()->subMinutes(5);
        
        $apiCalls = DB::table('security_events')
            ->where('event', 'api_call')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $timeWindow)
            ->count();

        return $apiCalls >= 100; // 100 API calls in 5 minutes
    }

    /**
     * Check for suspicious content
     */
    private function checkSuspiciousContent(array $logEntry): bool
    {
        if ($logEntry['event'] !== 'content_moderation') {
            return false;
        }

        $severity = $logEntry['severity'] ?? 'info';
        return $severity === 'high' || $severity === 'critical';
    }

    /**
     * Check for device abuse
     */
    private function checkDeviceAbuse(array $logEntry): bool
    {
        if ($logEntry['event'] !== 'device_fingerprint') {
            return false;
        }

        $context = $logEntry['context'];
        return $context['suspicious'] ?? false;
    }

    /**
     * Trigger security alert
     */
    private function triggerSecurityAlert(string $pattern, array $logEntry): void
    {
        $alert = [
            'pattern' => $pattern,
            'severity' => $this->getAlertSeverity($pattern),
            'user_id' => $logEntry['user_id'],
            'ip_address' => $logEntry['ip_address'],
            'timestamp' => now()->toISOString(),
            'context' => $logEntry,
        ];

        // Log alert
        Log::channel('security')->warning("Security Alert: {$pattern}", $alert);
        
        // Store alert in database
        $this->storeSecurityAlert($alert);
        
        // Send notifications
        $this->sendSecurityNotifications($alert);
    }

    /**
     * Get alert severity
     */
    private function getAlertSeverity(string $pattern): string
    {
        $severityMap = [
            'multiple_failed_logins' => 'high',
            'unusual_location_access' => 'medium',
            'rapid_api_calls' => 'medium',
            'suspicious_content' => 'high',
            'device_abuse' => 'high',
        ];

        return $severityMap[$pattern] ?? 'low';
    }

    /**
     * Store security event in database
     */
    private function storeSecurityEvent(array $logEntry): void
    {
        DB::table('security_events')->insert([
            'event' => $logEntry['event'],
            'user_id' => $logEntry['user_id'],
            'ip_address' => $logEntry['ip_address'],
            'user_agent' => $logEntry['user_agent'],
            'severity' => $logEntry['severity'],
            'context' => json_encode($logEntry['context']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Store security alert in database
     */
    private function storeSecurityAlert(array $alert): void
    {
        DB::table('security_alerts')->insert([
            'pattern' => $alert['pattern'],
            'severity' => $alert['severity'],
            'user_id' => $alert['user_id'],
            'ip_address' => $alert['ip_address'],
            'context' => json_encode($alert['context']),
            'resolved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Send security notifications
     */
    private function sendSecurityNotifications(array $alert): void
    {
        $channels = $this->config['alert_channels'];
        
        if ($channels['email'] ?? false) {
            $this->sendEmailAlert($alert);
        }
        
        if ($channels['slack'] ?? false) {
            $this->sendSlackAlert($alert);
        }
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(array $alert): void
    {
        $recipients = config('security_monitoring.email_recipients', []);
        
        foreach ($recipients as $email) {
            Mail::raw("Security Alert: {$alert['pattern']}\n\n" . json_encode($alert, JSON_PRETTY_PRINT), function ($message) use ($email, $alert) {
                $message->to($email)
                    ->subject("Security Alert: {$alert['pattern']}")
                    ->priority(1);
            });
        }
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(array $alert): void
    {
        $webhookUrl = config('security_monitoring.slack_webhook_url');
        
        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'text' => "Security Alert: {$alert['pattern']}",
            'attachments' => [
                [
                    'color' => $this->getSlackColor($alert['severity']),
                    'fields' => [
                        [
                            'title' => 'Pattern',
                            'value' => $alert['pattern'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => $alert['severity'],
                            'short' => true,
                        ],
                        [
                            'title' => 'User ID',
                            'value' => $alert['user_id'] ?? 'N/A',
                            'short' => true,
                        ],
                        [
                            'title' => 'IP Address',
                            'value' => $alert['ip_address'] ?? 'N/A',
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];

        // Send to Slack (implementation depends on your Slack integration)
        // This is a placeholder - you would implement actual Slack integration
    }

    /**
     * Get Slack color for severity
     */
    private function getSlackColor(string $severity): string
    {
        $colors = [
            'low' => 'good',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
        ];

        return $colors[$severity] ?? 'good';
    }

    /**
     * Calculate distance between two points
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c / 1000; // Convert to kilometers
    }

    /**
     * Get security statistics
     */
    public function getSecurityStats(string $timeframe = '24h'): array
    {
        $timeWindow = $this->getTimeWindow($timeframe);
        
        $stats = [
            'timeframe' => $timeframe,
            'total_events' => DB::table('security_events')
                ->where('created_at', '>=', $timeWindow)
                ->count(),
            'events_by_type' => DB::table('security_events')
                ->where('created_at', '>=', $timeWindow)
                ->select('event', DB::raw('count(*) as count'))
                ->groupBy('event')
                ->get()
                ->pluck('count', 'event')
                ->toArray(),
            'events_by_severity' => DB::table('security_events')
                ->where('created_at', '>=', $timeWindow)
                ->select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity')
                ->toArray(),
            'total_alerts' => DB::table('security_alerts')
                ->where('created_at', '>=', $timeWindow)
                ->count(),
            'unresolved_alerts' => DB::table('security_alerts')
                ->where('created_at', '>=', $timeWindow)
                ->where('resolved', false)
                ->count(),
        ];

        return $stats;
    }

    /**
     * Get time window for statistics
     */
    private function getTimeWindow(string $timeframe): \Carbon\Carbon
    {
        $timeframes = [
            '1h' => 1,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
        ];

        $hours = $timeframes[$timeframe] ?? 24;
        return now()->subHours($hours);
    }

    /**
     * Resolve security alert
     */
    public function resolveAlert(int $alertId, string $resolution = ''): bool
    {
        return DB::table('security_alerts')
            ->where('id', $alertId)
            ->update([
                'resolved' => true,
                'resolution' => $resolution,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get unresolved alerts
     */
    public function getUnresolvedAlerts(): array
    {
        return DB::table('security_alerts')
            ->where('resolved', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
