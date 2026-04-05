# TODO — fwber Immediate Action Items

> **Version:** 1.6.8
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Deploy NodeInfo 500 Fix to Hetzner**: Push the new `NodeInfoController` guard through the Hetzner deploy path and confirm `/nodeinfo/2.0` stops 500ing live.
- [ ] **Re-run Frontend GitHub Build Under Node 24**: Confirm the frontend workflow now goes green after aligning GitHub Actions to Node.js 24.
- [ ] **Verify Live Frontend API Recovery**: Confirm the dashboard and E2E restore calls now hit `api.fwber.me` instead of `www.fwber.me/api/*` after Vercel finishes deploying the updated frontend.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the backend/public-route repair deploy.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Once the live stack is stable, plan the broader feature reactivation sweep the user now wants.

## 🟡 High: Product Restoration
- [ ] **Audit Remaining Dead Settings Links**: Continue replacing leftover settings entries that still point at retired/non-core surfaces with live restored or core destinations.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.

## ✅ Recently Completed
- [x] **NodeInfo 500 Recovery + Frontend CI Runtime Fix**: Hardened discovery routes against missing federation columns and aligned the frontend GitHub workflow to Node.js 24.
- [x] **Hetzner Log ACL Deploy Fix**: Removed the broken log permission override, switched to ACL-based shared log access, and repaired the live Hetzner server log directory ACLs.
- [x] **Hetzner Backend Stability Repair**: Replaced the broken root route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables.
- [x] **Frontend Lockfile Resync**: Regenerated `fwber-frontend/package-lock.json` and validated with fresh `npm ci` + `npm run build`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
