# TODO — fwber Immediate Action Items

> **Version:** 1.4.9
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Hetzner VPS Provisioning Execution**: Complete server creation, DNS cutover, package install, env setup, and service configuration using the deployment docs plus `ops/hetzner/` templates.
- [ ] **Redeploy After Restoration Phases**: Re-run deployment now that AI, premium/billing, marketplace/merchant, geo-aware merchant discovery, merchant trust scoring, merchant review prioritization, deployment health verification, smoke-check automation, report artifacts, and remediation diagnostics are restored on top of the migration-hardening work.
- [ ] **Fix Live Health Route Drift**: The current reachable `api.fwber.me` deployment still returns `404` for `/api/health`, `/api/health/liveness`, and `/api/health/readiness`; ensure the live backend is redeployed from the current branch before cutover sign-off.
- [ ] **Fix Live Geo Domain Drift**: The current reachable `geo.fwber.me/nearby` contract returns a Vercel `404 deployment could not be found`; correct DNS/proxy/service routing so the geo microservice is actually reachable.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in an authenticated deployment environment with real secrets.
- [ ] **Provision Smoke-Test Tokens/Keys**: Create or document production-safe smoke-test accounts/tokens plus the Reverb app key path so `ops/hetzner/scripts/smoke-check.sh` can run its optional authenticated and websocket probes in production.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **Smoke Check Diagnostics & Remediation Hints**: Smoke-check reports now explain likely causes and next steps for common live drift patterns such as stale backend routes and geo-domain misrouting.
- [x] **Smoke Check Report Artifacts & Live Drift Detection**: Added JSON/Markdown smoke-check reports, timestamped deploy-report directories, and confirmed current live drift on `/api/health*` and `geo.fwber.me`.
- [x] **Hetzner Post-Deploy Smoke Checks**: Added `ops/hetzner/scripts/smoke-check.sh`, deploy-script smoke-check integration, and documentation for public, authenticated, and websocket rollout validation.
- [x] **Deployment Health & Verification Surface**: Activated `/api/health*`, added centralized dependency health evaluation, added `php artisan deploy:verify`, and updated Hetzner deployment docs.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
