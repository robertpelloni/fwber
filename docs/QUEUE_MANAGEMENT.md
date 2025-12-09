# Queue Management Strategy

## Overview
FWBer uses Laravel Queues backed by Redis to handle background tasks asynchronously. This ensures the application remains responsive even when performing heavy operations like sending emails, processing payments, or cleaning up data.

## Queue Architecture

We utilize a priority-based queue system with three distinct queues:

1.  **`high`**: Critical tasks that must be processed immediately (e.g., OTP emails, payment confirmations).
2.  **`default`**: Standard background tasks (e.g., subscription cleanup, data aggregation).
3.  **`notifications`**: Bulk notifications (e.g., event reminders, marketing emails) that can tolerate slight delays.

### Worker Configuration
The queue worker is configured to process jobs in strict priority order:
```bash
php artisan queue:work --queue=high,default,notifications --tries=3 --timeout=120
```
This means the worker will always empty the `high` queue before moving to `default`, and `default` before `notifications`.

## Job Assignments

| Job Class | Queue | Frequency | Description |
| :--- | :--- | :--- | :--- |
| `SendEventReminders` | `notifications` | Hourly | Sends reminders to event attendees. |
| `CleanupExpiredSubscriptions` | `default` | Daily | Revokes premium access for expired subscriptions. |
| `ExpireBoosts` | `default` | Every 15m | Expires active profile boosts. |
| `StripeWebhookController` | `default` | On Event | Webhook processing (if queued). |

## Monitoring

### Failed Jobs
Failed jobs are stored in the `failed_jobs` database table.
- **View**: `php artisan queue:failed`
- **Retry**: `php artisan queue:retry {id}`
- **Flush**: `php artisan queue:flush`

See [JOB_FAILURES_RUNBOOK.md](operations/JOB_FAILURES_RUNBOOK.md) for detailed troubleshooting steps.

### Horizon (Optional)
For advanced monitoring, Laravel Horizon can be installed to visualize queue throughput, runtime, and failures in real-time.

## Deployment
The queue worker is deployed as a separate service in `docker-compose.prod.yml`:
- **Service**: `queue-worker`
- **Image**: Same as `laravel` backend
- **Command**: Overridden to run `queue:work`
