# Post-Deploy Smoke Check Script — Implementation Notes

**Date:** 2026-04-04  
**Version:** 1.4.7

## Problem
The repo already had:
- Hetzner bootstrap scripts
- a deploy script
- public health endpoints
- a shared `php artisan deploy:verify` command

But there was still no single shell-level smoke check that validated the public contract exposed by the restored stack after a real deploy.

That gap mattered because post-cutover verification still required a human to remember and execute multiple curls and UI checks manually.

## Implementation Summary
Added:
- `ops/hetzner/scripts/smoke-check.sh`

The script is designed to be:
- safe for repeated post-deploy use
- shell-only and dependency-light
- useful on the server itself or from any machine with `curl`
- optionally deeper when auth tokens and the Reverb app key are available

## What It Checks
### Baseline checks
- frontend reachability
- `/api/health`
- `/api/health/liveness`
- `/api/health/readiness`
- invalid-login contract (`422 Invalid credentials`)
- public roast preview contract
- geo-service nearby endpoint

### Local-server check
If a backend checkout exists locally and `FWBER_SKIP_LOCAL_ARTISAN` is not set:
- runs `php artisan deploy:verify --json`

### Optional authenticated checks
When tokens are supplied via env vars:
- premium plans/status
- merchant dashboard
- moderation dashboard
- merchant moderation queue

### Optional websocket check
When `FWBER_REVERB_APP_KEY` is supplied:
- performs a real websocket upgrade probe against Reverb via `openssl s_client`
- expects `101 Switching Protocols`

## Integration Follow-Through
Updated:
- `ops/hetzner/scripts/deploy-backend.sh`
- `DEPLOY.md`
- `docs/ai/deployment/hetzner-vercel-production.md`
- `docs/deployment/HETZNER_VERCEL_DEPLOYMENT.md`

The deploy script now supports:
```bash
FWBER_RUN_SMOKE_CHECK=1 /var/www/fwber/repo/ops/hetzner/scripts/deploy-backend.sh
```

This keeps the smoke check opt-in during deployment so operations are not surprised by stricter validation until the correct env tokens/keys are available.

## Key Decisions
### 1. Public-contract checks first
The script focuses primarily on public and semi-public runtime surfaces because those are the fastest signals that a deploy is globally broken.

### 2. Auth checks are optional
Premium, merchant, and moderation routes are valuable to test, but forcing them would make the script unusable before smoke-test accounts or tokens exist.

### 3. Websocket validation should be real when possible
A simple HTTPS reachability check against the websocket host is weaker than a real upgrade attempt, so the script performs an actual websocket handshake whenever the app key is available.
