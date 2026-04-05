# TODO — fwber Immediate Action Items

> **Version:** 1.8.6
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Confirm Latest GitHub Hetzner Backend Deploy Goes Green**: Verify the push containing roast warmup stabilization finishes successfully end-to-end.
- [ ] **Verify Latest Live Surface Restorations Together**: Check `/friends`, `/activity`, `/notifications`, `/settings/travel`, `/events`, `/wallet`, referral signup, vouch links, roast preview, and video-call initiation in one signed-in production pass.
- [ ] **Verify Live Frontend API Recovery**: Confirm wallet, referral, vouch, dashboard, roast preview, and E2E restore calls all hit `api.fwber.me` correctly after the latest frontend deploys.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge, websocket/broadcast-auth behavior, and live video signaling after the recent backend/public-route repairs.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Continue the next restoration wave now that referral/video dead zones are back online.

## 🟡 High: Product Restoration
- [ ] **Resolve Remaining Gift-Specific Dead Flows**: Gift UI still points at wallet-linked token spending paths that may need either compact restoration or honest retirement.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment, now including referral commissions.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.
- [ ] **Real-Device Notification + Video QA**: Verify foreground/background notifications plus actual device/browser WebRTC call flows on physical devices.

## ✅ Recently Completed
- [x] **Smoke Roast Warmup Stabilization**: Warm the public roast preview once before the asserted smoke check to reduce transient false-negative deploy failures.
- [x] **Smoke Check Timeout + Roast Fallback Hardening**: Added a bounded websocket smoke timeout and hardened public roast previews against broader AI-driver failures.
- [x] **Hetzner Nginx Sync Helper Integration**: Added a root-owned helper path on the live server and updated the deploy script to use it for tracked nginx config refresh during GitHub deploys.
- [x] **Referral, Payout & Video Chat Restoration**: Restored referral validation, referral rewards/commissions, referral/vouch links, video-call backend endpoints, and expanded `/wallet` into a wallet + referrals + payout hub.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
