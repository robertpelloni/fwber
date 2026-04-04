# TODO — fwber Immediate Action Items

> **Version:** 1.5.6
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Re-run Final Public Smoke Cutover**: Re-run the smoke-enabled deploy path now that the websocket probe uses a valid handshake key, and confirm a clean no-false-negative summary across API, geo, and websocket surfaces.
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
- [x] **WebSocket Smoke Handshake Fix**: Fixed the smoke-check websocket probe so it uses a valid handshake key and stops producing false-negative `400 Invalid Sec-WebSocket-Key` failures.
- [x] **Deploy Script Privilege Hardening**: `deploy-backend.sh` now auto-uses `sudo` for systemd/nginx actions when run as a non-root operator.
- [x] **Hetzner Backend Execution & Database Migration**: Deployed fwber backend services on Hetzner, provisioned local MySQL, imported DreamHost production data, and verified Redis/Reverb/geo/queue health locally.
- [x] **Smoke Report Notification Publisher**: Added compact notification artifact generation and optional webhook publishing for smoke + drift report summaries.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
