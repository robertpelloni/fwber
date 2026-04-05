# TODO — fwber Immediate Action Items

> **Version:** 1.6.9
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Deployment & Verification
- [ ] **Verify Live Frontend Authenticated Runtime End-to-End**: Public bundle inspection confirms `api.fwber.me`, `ws.fwber.me`, and `broadcasting/auth`, but authenticated dashboard/E2E/realtime behavior should still be checked in-browser with a real user session.
- [ ] **Verify In-App Realtime UX, Not Just Handshake**: The websocket upgrade probe now passes, but the live header connection badge / private broadcast auth flow should still be checked from the actual frontend.
- [ ] **Decide Mercure Fate Explicitly**: `mercure.fwber.me` is still not part of a healthy live contract; either provision it properly or remove it from the active public surface.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s before broadening restoration further.
- [ ] **Map Remaining Removed Features for Full Restoration**: Once the live stack is stable, plan the broader feature reactivation sweep the user now wants.
- [ ] **Stabilize Browser Automation Harness**: `agent-browser` daemon restarts are timing out locally, which is now the main blocker to richer autonomous browser-side verification.

## 🟡 High: Product Restoration
- [ ] **Audit Remaining Dead Settings Links**: Continue replacing leftover settings entries that still point at retired/non-core surfaces with live restored or core destinations.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.

## ✅ Recently Completed
- [x] **Frontend Workflow Install Strategy Fix**: Switched the dedicated frontend GitHub workflow to `npm install --no-fund --no-audit` to get past platform-sensitive optional dependency drift.
- [x] **Frontend/Backend Contract Verification Sweep**: Confirmed the frontend GitHub workflow is green again and verified that the live `www.fwber.me` bundles point at `https://api.fwber.me`, `ws.fwber.me`, and `broadcasting/auth`.
- [x] **Hetzner Smoke/Deploy Contract Hardening**: Normalized smoke-check API base handling, auto-discovered the Reverb app key for websocket probes, re-synced tracked nginx configs during deploy, and re-verified the live stack at **9 passes / 3 warnings / 0 failures**.
- [x] **NodeInfo 500 Recovery + Frontend CI Runtime Fix**: Hardened discovery routes against missing federation columns and aligned the frontend GitHub workflow to Node.js 24.
- [x] **Hetzner Log ACL Deploy Fix**: Removed the broken log permission override, switched to ACL-based shared log access, and repaired the live Hetzner server log directory ACLs.
- [x] **Hetzner Backend Stability Repair**: Replaced the broken root route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
