# TODO — fwber Immediate Action Items

> **Version:** 1.6.6
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Re-run Hetzner Backend Deploy After ACL Fix**: Confirm the backend deploy workflow now succeeds after the log ACL fix and backend stability repair are both present in the deployed script/code path.
- [ ] **Re-check `api.fwber.me/` and Dashboard Endpoints Live**: Verify the backend root route and dashboard endpoints stop 500ing in production after the repaired deploy completes.
- [ ] **Re-run Frontend GitHub Workflow After Lockfile Resync**: Confirm `frontend-build.yml` goes green once the resynced lockfile is in the workflow run context.
- [ ] **Verify Live Frontend API Recovery**: Confirm the dashboard and E2E restore calls now hit `api.fwber.me` instead of `www.fwber.me/api/*` after Vercel finishes deploying the updated frontend.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the backend/public-route repair deploy.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.

## 🟡 High: Product Restoration
- [ ] **Map Remaining Removed Features vs Current Live Errors**: Before re-enabling all archived systems wholesale, finish stabilizing the live stack so restoration does not compound unresolved runtime drift.
- [ ] **Audit Remaining Dead Settings Links**: Continue replacing leftover settings entries that still point at retired/non-core surfaces with live restored or core destinations.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## ✅ Recently Completed
- [x] **Hetzner Log ACL Deploy Fix**: Removed the broken log permission override, switched to ACL-based shared log access, and repaired the live Hetzner server log directory ACLs.
- [x] **Hetzner Backend Stability Repair**: Replaced the broken root route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables.
- [x] **Frontend Lockfile Resync**: Regenerated `fwber-frontend/package-lock.json` and validated with fresh `npm ci` + `npm run build`.
- [x] **Workflow Stabilization Sweep**: Fixed backend/frontend workflow drift and removed duplicate auto-failing CI/deploy noise from legacy workflows.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
