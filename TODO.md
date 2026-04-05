# TODO — fwber Immediate Action Items

> **Version:** 1.6.5
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Deploy v1.6.5 Hetzner Backend Stability Repair**: Pull the new backend patch onto Hetzner, run the corrective match-table migration, refresh route/config caches, and verify `api.fwber.me/` plus dashboard endpoints stop throwing live 500s.
- [ ] **Repair Live Reverb Handshake Contract**: Re-test `ws.fwber.me` using the real production app key after the backend patch deploy and determine whether the remaining websocket issue is config/auth related or a true runtime failure.
- [ ] **Repair / Replace Mercure Surface**: `mercure.fwber.me` still returns `502` because nothing is listening on `127.0.0.1:3000`; either provision the intended service or remove the dead public route from the active production contract.
- [ ] **Re-run Frontend GitHub Workflow After Lockfile Resync**: Confirm `frontend-build.yml` now goes green after the updated `package-lock.json` lands.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## 🟡 High: Product Restoration
- [ ] **Map Remaining Removed Features vs Current Live Errors**: Before re-enabling all archived systems wholesale, finish stabilizing the live stack so restoration does not compound unresolved runtime drift.
- [ ] **Audit Remaining Dead Settings Links**: Continue replacing leftover settings entries that still point at retired/non-core surfaces with live restored or core destinations.
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.

## ✅ Recently Completed
- [x] **Hetzner Backend Stability Repair**: Replaced the broken root route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables.
- [x] **Frontend Lockfile Resync**: Regenerated `fwber-frontend/package-lock.json` and validated with fresh `npm ci` + `npm run build`.
- [x] **Workflow Stabilization Sweep**: Fixed backend/frontend workflow drift and removed duplicate auto-failing CI/deploy noise from legacy workflows.
- [x] **GitHub Hetzner Deploy Validation**: Added Hetzner GitHub secrets, triggered the workflow, and confirmed a green GitHub-driven deploy with 9 smoke-check passes and 0 failures.
- [x] **GitHub Hetzner Deploy Rust Path Fix**: Patched the deploy script so CI-triggered non-login SSH sessions use the rustup Cargo toolchain required by `fwber-geo`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
