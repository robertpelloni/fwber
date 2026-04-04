# TODO — fwber Immediate Action Items

> **Version:** 1.5.8
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Verify Live Vercel Rollout of Restored Navigation**: Confirm the new restored-feature nav/cards are visible on the live frontend after the latest Vercel deployment finishes.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **Provision Smoke-Test Tokens/Keys**: Create or document production-safe smoke-test accounts/tokens plus the Reverb app key path so the full authenticated/websocket smoke flow can run in production.
- [ ] **Wire Chat/Webhook Notifications**: Provide a real `FWBER_SMOKE_NOTIFY_WEBHOOK_URL` target if you want deploy notifications delivered automatically outside the report directory.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## 🟡 High: Product Polish
- [ ] **Audit Remaining Dead Settings Links**: Continue replacing leftover settings entries that still point at retired/non-core surfaces with live restored or core destinations.
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **DreamHost Secret Rotation / Cleanup**: Rotate migrated credentials where appropriate now that the backend and DB have been moved to Hetzner.

## ✅ Recently Completed
- [x] **Restored Feature Navigation Surface**: Exposed Gold, Roast, Merchant, and moderator surfaces in the authenticated app sidebar, mobile nav, dashboard, and settings so restored systems are actually visible.
- [x] **Hetzner Public Smoke Validation**: Full smoke-enabled deploy run now passes with 9 passes, 3 expected auth-token warnings, and 0 failures.
- [x] **Hetzner Script Executable Bits**: Marked core Hetzner ops scripts executable in git so server pulls retain runnable permissions.
- [x] **WebSocket Smoke Handshake Fix**: Fixed the smoke-check websocket probe so it uses a valid handshake key and stops producing false-negative failures.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
