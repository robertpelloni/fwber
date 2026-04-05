# TODO — fwber Immediate Action Items

> **Version:** 1.9.0
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Verify Latest Unlock Restoration Live**: Confirm match insights unlock, private photo unlock, content-unlock token debits, and public profile locked-photo reveals work against the Hetzner backend.
- [ ] **Verify Latest Live Surface Restorations Together**: Check `/friends`, `/activity`, `/notifications`, `/settings/travel`, `/events`, `/wallet`, referral signup, vouch links, roast preview, gifts, boosts, match insights unlocks, and private photo unlocks in one signed-in production pass.
- [ ] **Verify Live Frontend API Recovery**: Confirm wallet, referral, vouch, dashboard, roast preview, gifts, boosts, match insights, private photo unlocks, and E2E restore calls all hit `api.fwber.me` correctly after the latest frontend deploys.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge, websocket/broadcast-auth behavior, live video signaling, and any gift-related notification realtime behavior.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Root-Cause The Roast First-Hit Flake**: Even though the deploy is no longer blocked by it, keep investigating why smoke sees a transient 500 while manual steady-state requests can return 200.

## 🟡 High: Product Restoration
- [ ] **Restore / Retire Remaining Token-Gated Filters + Adjacent Spend Paths**: Token-gated filters and any remaining wallet-linked paywall surfaces still need compact restoration or honest retirement.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment, now including referral commissions and boost card purchases.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.
- [ ] **Real-Device Notification + Video QA**: Verify foreground/background notifications plus actual device/browser WebRTC call flows on physical devices.

## ✅ Recently Completed
- [x] **Token-Gated Unlock Surface Restoration**: Restored match insights unlocks, private photo unlocks, generic content unlock ledger, and frontend locked/unlocked UX for these token-spend surfaces.
- [x] **Profile Boost Restoration**: Restored boost purchase/status/history endpoints and reconnected the boost CTA on `/matches` to a real backend contract.
- [x] **Gift Economy Surface Restoration**: Restored gift catalog/send/received endpoints, gift notifications, and the wallet gifts tab so token-era gift UI has a real backend again.
- [x] **Hetzner backend deploy is green**: GitHub `Deploy Backend (Hetzner)` run succeeded after privilege and smoke hardening.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
