# Job Failures Runbook

## Overview
This runbook details the procedures for identifying, diagnosing, and resolving failed background jobs in the FWBer application.

## 1. Identification

### Check Failed Jobs
Run the following Artisan command to list all failed jobs:
```bash
php artisan queue:failed
```
This will display a table with:
- ID
- Connection
- Queue
- Class
- Failed At

### Logs
Check `storage/logs/laravel.log` for stack traces associated with the job failure. Search for "Job failed" or the specific job class name.

## 2. Common Failure Scenarios

### A. External API Timeout (e.g., Stripe)
- **Symptom**: `Connection timed out` or `503 Service Unavailable` exceptions.
- **Resolution**: These are usually transient. Retry the job.
- **Prevention**: Ensure jobs have appropriate `tries` and `backoff` configured.

### B. Database Deadlock
- **Symptom**: `Deadlock found when trying to get lock`.
- **Resolution**: Retry the job.
- **Prevention**: Review database transaction logic if this occurs frequently.

### C. Invalid Payload / Logic Error
- **Symptom**: `TypeError`, `Undefined index`, or validation errors.
- **Resolution**:
  1. **Do NOT retry** immediately.
  2. Fix the underlying code bug.
  3. Deploy the fix.
  4. Retry the job.

## 3. Resolution Steps

### Retrying a Specific Job
If the failure was transient (e.g., network blip), retry the specific job by ID:
```bash
php artisan queue:retry {id}
```

### Retrying All Failed Jobs
To retry all failed jobs in the registry:
```bash
php artisan queue:retry all
```

### Pruning Failed Jobs
If a job cannot be salvaged or is no longer relevant, remove it from the failed jobs table:
```bash
php artisan queue:forget {id}
```
To flush all failed jobs:
```bash
php artisan queue:flush
```

## 4. Monitoring & Alerting
- **Horizon**: If installed, use the Horizon dashboard (`/horizon`) for real-time queue monitoring.
- **Health Check**: Ensure the `queue:work` process is running via Supervisor or Docker health checks.
