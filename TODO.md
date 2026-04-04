# TODO — fwber Immediate Action Items

> **Version:** 1.5.5
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Run Final Public Smoke Cutover**: Execute the smoke-enabled deploy flow against the now-Hetzner-hosted fwber runtime and review summary, drift, notification, diagnostics, fingerprints, and DNS appendix artifacts.
- [ ] **Verify Public Frontend → API Path**: Confirm the Vercel frontend is using the Hetzner-hosted `api.fwber.me` path cleanly after DNS cutover.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **Provision Smoke-Test Tokens/Keys**: Create or document production-safe smoke-test accounts/tokens plus the Reverb app key path so the full authenticated/websocket smoke flow can run in production.
- [ ] **Wire Chat/Webhook Notifications**: Provide a real `FWBER_SMOKE_NOTIFY_WEBHOOK_URL` target if you want deploy notifications delivered automatically outside the report directory.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **DreamHost Secret Rotation / Cleanup**: Rotate migrated credentials where appropriate now that the backend and DB have been moved to Hetzner.

## ✅ Recently Completed
- [x] **Deploy Script Privilege Hardening**: `deploy-backend.sh` now auto-uses `sudo` for systemd/nginx actions when run as a non-root operator.
- [x] **Hetzner Backend Execution & Database Migration**: Deployed fwber backend services on Hetzner, provisioned local MySQL, imported DreamHost production data, and verified Redis/Reverb/geo/queue health locally.
- [x] **Smoke Report Notification Publisher**: Added compact notification artifact generation and optional webhook publishing for smoke + drift report summaries.
- [x] **Smoke Report Drift Diff**: Added comparison tooling that highlights summary, diagnostic, fingerprint, and DNS changes between smoke-check runs.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
