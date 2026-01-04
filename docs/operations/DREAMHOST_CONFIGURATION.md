# DreamHost Shared Hosting Configuration Guide

This guide provides specific instructions for configuring fwber on DreamHost Shared Hosting, where you do not have `sudo` access or `supervisor`/`systemd` for background processes.

## 1. PHP Configuration

DreamHost allows multiple PHP versions. Ensure you are using PHP 8.2 or higher.

**Find your PHP path:**
```bash
# We confirmed this is version 8.2+
/usr/bin/php -v
```
*Use `/usr/bin/php` in all your cron jobs.*

## 2. Environment Optimization (Database Driver)

Since Shared Hosting typically does not provide Redis, use the **Database** driver for Cache, Queue, and Sessions. This is much faster and more reliable than the `file` driver.

**Update your `.env` file:**
```dotenv
CACHE_DRIVER=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

*Note: The necessary tables (`cache`, `jobs`, `sessions`) are already created by your migrations.*

## 3. Cron Jobs (Critical)

You must set up Cron jobs to handle scheduled tasks, background queues, and service uptime.

**Edit Crontab:**
```bash
crontab -e
```

**Add the following lines (replace `/home/username/fwber` with your actual path):**

### A. Laravel Scheduler (Every Minute)
Runs scheduled tasks like database backups, cleanup, and subscription checks.
```cron
* * * * * /usr/bin/php /home/username/fwber/fwber-backend/artisan schedule:run >> /dev/null 2>&1
```

### B. Queue Worker (Keep-Alive)
Since we can't use Supervisor, we use the Scheduler to process the queue.
Ensure `app/Console/Kernel.php` or `routes/console.php` schedules the queue worker, OR run a simple worker via cron that stops when empty (to prevent memory leaks).

**Recommended for Shared Hosting:**
Run a worker that processes jobs for 55 seconds then exits. Cron restarts it every minute.
```cron
* * * * * /usr/bin/php /home/username/fwber/fwber-backend/artisan queue:work --stop-when-empty --max-time=55 >> /home/username/fwber/fwber-backend/storage/logs/queue.log 2>&1
```

### C. Mercure Real-Time Hub (Keep-Alive)
Ensures the real-time server stays running.
```cron
* * * * * /bin/bash /home/username/fwber/fwber-backend/scripts/check_mercure_status.sh
```

## 3. Process Management

On shared hosting, long-running processes might be killed by the system's OOM (Out of Memory) killer or process reaper.

*   **Mercure**: Configured with `GOMEMLIMIT=32MiB` in `start_mercure_shared.sh` to stay under the radar.
*   **Queue**: The `--max-time=55` flag ensures PHP processes don't grow too large or hang indefinitely.

## 4. Troubleshooting

### "Command not found" in Cron
Cron runs with a minimal environment. Always use absolute paths:
*   Instead of `php`, use `/usr/bin/php8.2`
*   Instead of `artisan`, use `/home/username/fwber/fwber-backend/artisan`

### "Permission denied"
Ensure your scripts are executable:
```bash
chmod +x /home/username/fwber/fwber-backend/scripts/*.sh
```

### Logs
Check these files if things aren't working:
*   `fwber-backend/storage/logs/laravel.log` (App errors)
*   `fwber-backend/storage/logs/queue.log` (Queue worker output)
*   `fwber-backend/mercure.log` (Real-time server output)
*   `fwber-backend/mercure_monitor.log` (Restart events)
