# TODO — fwber Immediate Action Items

> **Version:** 1.8.3
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Re-run GitHub Hetzner Backend Deploy After Privilege Fix**: Confirm the latest `Deploy Backend (Hetzner)` workflow goes green after the optional nginx-sync privilege recovery patch.
- [ ] **Verify Latest Live Surface Restorations Together**: Check `/friends`, `/activity`, `/notifications`, `/settings/travel`, `/events`, `/wallet`, referral signup, vouch links, and video-call initiation in one signed-in production pass.
- [ ] **Verify Live Frontend API Recovery**: Confirm wallet, referral, vouch, dashboard, and E2E restore calls all hit `api.fwber.me` correctly after the latest frontend deploys.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge, websocket/broadcast-auth behavior, and live video signaling after the recent backend/public-route repairs.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Continue the next restoration wave now that referral/video dead zones are back online.

## 🟡 High: Product Restoration
- [ ] **Resolve Remaining Gift-Specific Dead Flows**: Gift UI still points at wallet-linked token spending paths that may need either compact restoration or honest retirement.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment, now including referral commissions.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.
- [ ] **Real-Device Notification + Video QA**: Verify foreground/background notifications plus actual device/browser WebRTC call flows on physical devices.

## ✅ Recently Completed
- [x] **Hetzner Deploy Privilege Recovery**: Hardened the deploy script so nginx config sync no longer aborts the whole deploy when the deploy user lacks passwordless sudo for filesystem writes.
- [x] **Referral, Payout & Video Chat Restoration**: Restored referral validation, referral rewards/commissions, referral/vouch links, video-call backend endpoints, and expanded `/wallet` into a wallet + referrals + payout hub.
- [x] **Wallet Surface Restoration**: Restored the wallet backend/API surface and added a real `/wallet` page so token-linked dead routes now land somewhere useful.
- [x] **Events Surface Restoration**: Restored the events backend/API surface, event invitation flow, and frontend pages for listing, viewing, and creating events.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
