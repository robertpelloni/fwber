# PROJECT_STATUS.md - fwber v1.4.7 (Hetzner Post-Deploy Smoke Checks)

**Date:** 2026-04-04
**Version:** 1.4.7 "Hetzner Post-Deploy Smoke Checks"
**Status:** ✅ **VERIFIED, COMMITTED, AND BETTER PREPARED FOR HETZNER CUTOVER**

---

## 🎯 What This Release Delivered
This release closed the next operational gap after adding health endpoints: it introduced a reusable shell-level smoke-check workflow for post-deploy validation.

Delivered:
- a reusable Hetzner smoke-check script
- opt-in deploy-script integration for automatic smoke checks after redeploys
- optional authenticated smoke probes for premium, merchant, and moderation routes
- optional websocket upgrade probing for Reverb
- deployment doc updates so the rollout path references real smoke automation instead of only manual steps

## 🚀 Operations Improvements
### Added `ops/hetzner/scripts/smoke-check.sh`
This script verifies:
- frontend reachability
- `php artisan deploy:verify --json` when run on the server with the backend checkout present
- `GET /api/health`
- `GET /api/health/liveness`
- `GET /api/health/readiness`
- invalid-login contract (`422 Invalid credentials`)
- public roast preview contract
- geo nearby endpoint contract

Optional env-driven checks:
- `FWBER_USER_BEARER_TOKEN` → premium plans + status
- `FWBER_MERCHANT_BEARER_TOKEN` → merchant dashboard
- `FWBER_MODERATOR_BEARER_TOKEN` → moderation dashboard + merchant queue
- `FWBER_REVERB_APP_KEY` → real websocket upgrade probe

### Updated `ops/hetzner/scripts/deploy-backend.sh`
The deploy script now:
- runs `php artisan deploy:verify` directly in the deploy sequence
- supports `FWBER_RUN_SMOKE_CHECK=1` to execute the smoke-check script automatically after service restarts

## 📚 Documentation Improvements
Updated rollout guidance in:
- `DEPLOY.md`
- `docs/ai/deployment/hetzner-vercel-production.md`
- `docs/deployment/HETZNER_VERCEL_DEPLOYMENT.md`
- `docs/SUBMODULE_DASHBOARD.md`

Added AI DevKit implementation/testing notes:
- `docs/ai/implementation/post-deploy-smoke-check-script.md`
- `docs/ai/testing/post-deploy-smoke-check-script.md`

## ✅ Validation
- Shell syntax validation passed:
  - `bash -n ops/hetzner/scripts/smoke-check.sh`
  - `bash -n ops/hetzner/scripts/deploy-backend.sh`
- Previous deploy-health validation remains in place:
  - `php artisan deploy:verify --json`
  - `tests/Feature/HealthEndpointsTest.php`

## ✅ Why This Matters
The project now has a stronger progression of deploy confidence:
1. health contract inside the backend (`/api/health*`)
2. local/server verification command (`php artisan deploy:verify`)
3. shell-level public smoke-check automation (`smoke-check.sh`)

That sequence makes live Hetzner cutover and future redeploys materially safer and easier to repeat.
