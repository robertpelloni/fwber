# Rate Limiting Validation Guide

Purpose: Validate advanced rate limiting configuration, behavior, and observability in staging/production.

Last updated: 2025-11-15

---

## Prerequisites

- `FEATURE_RATE_LIMITS=true` in `.env`
- Redis reachable (for counters)
- App running with `APP_ENV=staging` or `production`

---

## What we validate

1. Auth attempts limited (lockout beyond threshold)
2. API calls limited with 429 responses and headers
3. Suspicious activity detection logs warnings
4. Headers present: `X-RateLimit-Remaining`, `X-RateLimit-Reset`, `X-RateLimit-Action`

---

## Enable and verify

```bash
# Enable feature
php artisan tinker --execute="config(['features.rate_limits' => true]);"

# Clear and cache config
php artisan config:clear
php artisan config:cache
```

---

## Tests

### 1) Auth attempt limit

Expected policy: 5 attempts with refill 1/minute.

```bash
for /L %i in (1,1,7) do curl -s -o NUL -w "%{http_code}\n" -X POST http://localhost:8000/api/auth/login -H "Content-Type: application/json" -d "{\"email\":\"nope@example.com\",\"password\":\"wrong\"}"
```

Expected:
- First 5 return 401 (invalid credentials)
- Attempts 6-7 return 429 with `Retry-After` header

### 2) API call limit

```bash
for /L %i in (1,1,1100) do curl -s -o NUL -w "%{http_code}\n" http://localhost:8000/api/dashboard/stats -H "Authorization: Bearer <token>"
```

Expected:
- Eventually see `429` codes
- Response JSON includes `retry_after`, `remaining_tokens`, `reset_time`
- Headers include `X-RateLimit-*`

### 3) Suspicious activity logging

Trigger with high frequency calls to multiple actions within a short time window (simulate with parallel requests).

Check logs:

```bash
# Security log (JSON if LOG_FORMAT=json)
Get-Content -Tail 100 .\storage\logs\security-*.log

# Application logs
Get-Content -Tail 100 .\storage\logs\laravel-*.log
```

Look for:
- `Suspicious rate limiting activity detected`
- Context: `user_id`, `reasons`, `ip`, `user_agent`

---

## Observability

- Ensure `LOG_FORMAT=json` for structured logs
- Consider shipping logs to ELK/CloudWatch; build a dashboard panel for 429 rate and Retry-After distribution

---

## Rollback

If excessive false positives or degradation is observed:
- Toggle off with `FEATURE_RATE_LIMITS=false`
- Clear config cache: `php artisan config:clear && php artisan config:cache`
- Investigate and tune `config/rate_limiting.php`

---

## Tuning parameters

Edit `config/rate_limiting.php` or via `.env` overrides:

- `RATE_LIMIT_API_CALL_CAPACITY`, `RATE_LIMIT_API_CALL_REFILL`
- `RATE_LIMIT_AUTH_ATTEMPT_*`
- `RATE_LIMIT_IP_*` and `RATE_LIMIT_DEVICE_*`
- `RATE_LIMIT_LOG_ALL_EVENTS` / `RATE_LIMIT_LOG_SUSPICIOUS_ONLY`

---

## Sign-off checklist

- [ ] 429 responses occur per policy
- [ ] Headers present and accurate
- [ ] Suspicious activity logs created
- [ ] No major impact on normal users
- [ ] Feature flag toggle verified
