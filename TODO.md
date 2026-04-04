# TODO — fwber Immediate Action Items

> **Version:** 1.4.7
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Hetzner VPS Provisioning Execution**: Complete server creation, DNS cutover, package install, env setup, and service configuration using the deployment docs plus `ops/hetzner/` templates.
- [ ] **Redeploy After Restoration Phases**: Re-run deployment now that AI, premium/billing, marketplace/merchant, geo-aware merchant discovery, merchant trust scoring, merchant review prioritization, deployment health verification, and smoke-check automation are restored on top of the migration-hardening work.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in an authenticated deployment environment with real secrets.
- [ ] **Provision Smoke-Test Tokens/Keys**: Create or document production-safe smoke-test accounts/tokens plus the Reverb app key path so `ops/hetzner/scripts/smoke-check.sh` can run its optional authenticated and websocket probes in production.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **Hetzner Post-Deploy Smoke Checks**: Added `ops/hetzner/scripts/smoke-check.sh`, deploy-script smoke-check integration, and documentation for public, authenticated, and websocket rollout validation.
- [x] **Deployment Health & Verification Surface**: Activated `/api/health*`, added centralized dependency health evaluation, added `php artisan deploy:verify`, and updated Hetzner deployment docs.
- [x] **Merchant Review Prioritization**: Added merchant moderation queue priority scoring, search, and inline review-note workflows.
- [x] **Merchant Trust Scoring & Moderation**: Added trust-aware merchant scoring, moderator review endpoints, merchant moderation dashboard tab, and trust-weighted nearby marketplace ranking.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
