# TODO — fwber Immediate Action Items

> **Version:** 1.6.3
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Re-run Backend/Frontend GitHub Workflows After Sweep**: Confirm `backend-tests.yml` and `frontend-build.yml` both go green after the workflow stabilization patch lands.
- [ ] **Verify Live Frontend API Recovery**: Confirm the dashboard and E2E restore calls now hit `api.fwber.me` instead of `www.fwber.me/api/*` after Vercel finishes deploying the updated frontend.
- [ ] **Verify Live Realtime Recovery**: Confirm the header connection badge reaches Connected on the live site and that private channel auth now goes to `api.fwber.me/broadcasting/auth`.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is fully validated, decommission the old DreamHost fwber backend path and remove stale provider dependencies.

## 🟡 High: Product Polish
- [ ] **Audit Remaining Dead Settings Links**: Continue replacing leftover settings entries that still point at retired/non-core surfaces with live restored or core destinations.
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **DreamHost Secret Rotation / Cleanup**: Rotate migrated credentials where appropriate now that the backend and DB have been moved to Hetzner.

## ✅ Recently Completed
- [x] **Workflow Stabilization Sweep**: Fixed backend/frontend workflow drift and removed duplicate auto-failing CI/deploy noise from legacy workflows.
- [x] **GitHub Hetzner Deploy Validation**: Added Hetzner GitHub secrets, triggered the workflow, and confirmed a green GitHub-driven deploy with 9 smoke-check passes and 0 failures.
- [x] **GitHub Hetzner Deploy Rust Path Fix**: Patched the deploy script so CI-triggered non-login SSH sessions use the rustup Cargo toolchain required by `fwber-geo`.
- [x] **GitHub Backend Deploy Switched to Hetzner**: Replaced the stale DreamHost backend deployment workflow with a Hetzner-targeted GitHub Action using `ops/hetzner/scripts/deploy-backend.sh`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
