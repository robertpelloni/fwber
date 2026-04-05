# TODO — fwber Immediate Action Items

> **Version:** 1.7.0
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Deploy Friends Restoration to Hetzner**: Let the new friends schema + API surface deploy through the green Hetzner pipeline and verify the live `/friends` route works with a real session.
- [ ] **Verify Live Frontend Friends UX**: Confirm the restored `/friends` page, header nav, message-page link, and friend-request flows work in-browser after deploy.
- [ ] **Verify Live Frontend API Recovery**: Confirm dashboard and E2E restore calls still hit `api.fwber.me` correctly after the latest frontend deploys.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the recent backend/public-route repairs.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Continue the next restoration wave now that infra/deploy/runtime stability is much better.

## 🟡 High: Product Restoration
- [ ] **Restore Activity / Notifications Pages**: Bring back the `/activity` and `/notifications` routes so remaining dead links in the signed-in shell are eliminated.
- [ ] **Restore Travel Mode Surface**: Reintroduce the missing `/settings/travel` page since Settings still advertises it.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## ✅ Recently Completed
- [x] **Friends System Restoration**: Restored the friends backend/API surface, added a new `/friends` page, and put Friends back into authenticated navigation.
- [x] **Mercure Surface Retirement**: Added a tracked Hetzner nginx config that returns an explicit `410 Gone` for `mercure.fwber.me`, wired it into the deploy script, and verified the retired response live from the public internet.
- [x] **Frontend Workflow Install Strategy Fix**: Switched the dedicated frontend GitHub workflow to `npm install --no-fund --no-audit` to get past platform-sensitive optional dependency drift.
- [x] **Frontend/Backend Contract Verification Sweep**: Confirmed the frontend GitHub workflow is green again and verified that the live `www.fwber.me` bundles point at `https://api.fwber.me`, `ws.fwber.me`, and `broadcasting/auth`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
