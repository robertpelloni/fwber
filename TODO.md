# TODO — fwber Immediate Action Items

> **Version:** 1.7.1
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Deploy Dead-Surface Recovery to Production**: Let the latest frontend build deploy so `/activity`, `/notifications`, and `/settings/travel` are live on `www.fwber.me`.
- [ ] **Verify Live Friends + Notifications + Activity + Travel UX**: Confirm the newly restored routes work with a real signed-in session on production.
- [ ] **Verify Live Frontend API Recovery**: Confirm dashboard and E2E restore calls still hit `api.fwber.me` correctly after the latest frontend deploys.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the recent backend/public-route repairs.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Continue the next restoration wave now that infra/deploy/runtime stability is much better.

## 🟡 High: Product Restoration
- [ ] **Restore Wallet / Gift Dead Links Or Remove Them**: Notification and match surfaces still contain legacy `/wallet` destinations that should either be restored compactly or retired honestly.
- [ ] **Restore Lightweight Events Surface Or Retire It**: Notifications can still reference `/events`, so either bring back a lean events page or remove the route target from active UX.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## ✅ Recently Completed
- [x] **Dead Surface Recovery: Activity, Notifications, Travel**: Restored `/activity`, `/notifications`, and `/settings/travel` so prominent signed-in links no longer land on missing pages.
- [x] **Friends System Restoration**: Restored the friends backend/API surface, added a new `/friends` page, and put Friends back into authenticated navigation.
- [x] **Mercure Surface Retirement**: Added a tracked Hetzner nginx config that returns an explicit `410 Gone` for `mercure.fwber.me` and wired it into the deploy script so the dead upstream is no longer presented as a broken live service.
- [x] **Frontend Workflow Install Strategy Fix**: Switched the dedicated frontend GitHub workflow to `npm install --no-fund --no-audit` to get past platform-sensitive optional dependency drift.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
