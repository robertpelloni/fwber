# TODO — fwber Immediate Action Items

> **Version:** 1.8.0
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Deploy Events Restoration to Production**: Let the latest backend/frontend deploys ship the restored events surfaces and verify `/events`, `/events/[id]`, and `/events/create` with a real session.
- [ ] **Verify Latest Live Surface Restorations Together**: Check `/friends`, `/activity`, `/notifications`, `/settings/travel`, and `/events` in one signed-in pass on production.
- [ ] **Verify Live Frontend API Recovery**: Confirm dashboard and E2E restore calls still hit `api.fwber.me` correctly after the latest frontend deploys.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the recent backend/public-route repairs.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Continue the next restoration wave now that infra/deploy/runtime stability is much better.

## 🟡 High: Product Restoration
- [ ] **Restore Wallet / Gift Dead Links Or Retire Them**: Notification and match surfaces still contain legacy `/wallet` destinations that should either be restored compactly or retired honestly.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.

## ✅ Recently Completed
- [x] **Events Surface Restoration**: Restored the events backend/API surface, event invitation flow, and frontend pages for listing, viewing, and creating events.
- [x] **Dead Surface Recovery: Activity, Notifications, Travel**: Restored `/activity`, `/notifications`, and `/settings/travel` so prominent signed-in links no longer land on missing pages.
- [x] **Friends System Restoration**: Restored the friends backend/API surface, added a new `/friends` page, and put Friends back into authenticated navigation.
- [x] **Mercure Surface Retirement**: Added a tracked Hetzner nginx config that returns an explicit `410 Gone` for `mercure.fwber.me` and wired it into the deploy script so the dead upstream is no longer presented as a broken live service.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
