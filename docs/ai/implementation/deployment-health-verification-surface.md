# Deployment Health & Verification Surface — Implementation Notes

**Date:** 2026-04-04  
**Version:** 1.4.6

## Problem
The repo had provisioning docs and service templates for Hetzner, but deployment validation was still too manual. There was no single shared contract for:
- HTTP health checks
- CLI deployment verification
- readiness semantics tied to the actual active runtime config

That created risk during cutover, redeploys, and incident triage.

## Implementation Summary
### 1. Centralized health evaluation
Added:
- `fwber-backend/app/Services/HealthStatusService.php`

This service now builds a structured status payload used by both:
- `HealthController`
- `DeployVerifyCommand`

Checks included:
- database
- Redis
- cache
- storage writability
- queue connection config
- broadcast connection config
- memory/uptime metrics

## 2. Public API health surface
Activated routes in `fwber-backend/routes/api.php`:
- `/api/health`
- `/api/health/liveness`
- `/api/health/readiness`
- `/api/health/metrics`

### Route intent
- `health`: detailed dependency snapshot for operators and dashboards
- `liveness`: cheap process-alive probe
- `readiness`: safe-to-serve-traffic probe
- `metrics`: lightweight infrastructure hints for manual scaling/debugging

## 3. CLI verification
Added:
- `fwber-backend/app/Console/Commands/DeployVerifyCommand.php`

Command:
```bash
php artisan deploy:verify
php artisan deploy:verify --json
```

This gives a consistent server-side verification step that can be used during:
- first deploy on Hetzner
- redeploys after migrations
- incident triage
- future automation wrappers

## Key Decision
### Redis criticality is configuration-aware
Redis is only marked critical when the active runtime configuration depends on Redis-backed services such as cache, queue, session, or broadcast paths.

Reason:
A hard Redis requirement in every environment would create noisy false negatives for local validation or lean environments that intentionally run without Redis in the active path.

## Documentation Follow-Through
Updated:
- `DEPLOY.md`
- `docs/ai/deployment/hetzner-vercel-production.md`
- `docs/deployment/HETZNER_VERCEL_DEPLOYMENT.md`

These docs now explicitly include `php artisan deploy:verify` and `/api/health*` in post-deploy validation.
