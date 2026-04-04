# TODO — fwber Immediate Action Items

> **Version:** 1.5.4
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Public DNS Cutover for API**: Repoint `api.fwber.me` to Hetzner (`5.161.250.43`) so the now-deployed Hetzner backend becomes the public API.
- [ ] **Public DNS Cutover for Geo**: Repoint `geo.fwber.me` to Hetzner (`5.161.250.43`) so the live geo hostname reaches the deployed Rust geo service instead of the current wrong target.
- [ ] **Issue/Confirm Hetzner TLS for API + Geo**: After DNS cutover, issue or validate `api.fwber.me` and `geo.fwber.me` certificates on Hetzner and enable the final nginx vhosts.
- [ ] **Run Full Hetzner Smoke Cutover**: Execute the smoke-enabled deploy flow on Hetzner and review summary, drift, notification, diagnostics, fingerprints, and DNS appendix artifacts after public cutover.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in the Hetzner-hosted backend environment.
- [ ] **Provision Smoke-Test Tokens/Keys**: Create or document production-safe smoke-test accounts/tokens plus the Reverb app key path so the full authenticated/websocket smoke flow can run in production.
- [ ] **Wire Chat/Webhook Notifications**: Provide a real `FWBER_SMOKE_NOTIFY_WEBHOOK_URL` target if you want deploy notifications delivered automatically outside the report directory.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **DreamHost Backend Retirement**: Once Hetzner API cutover is validated, decommission the old DreamHost fwber backend path and remove stale DNS/provider dependencies.

## ✅ Recently Completed
- [x] **Hetzner Backend Execution & Database Migration**: Deployed fwber backend services on Hetzner, provisioned local MySQL, imported DreamHost production data, and verified Redis/Reverb/geo/queue health locally.
- [x] **Smoke Report Notification Publisher**: Added compact notification artifact generation and optional webhook publishing for smoke + drift report summaries.
- [x] **Smoke Report Drift Diff**: Added comparison tooling that highlights summary, diagnostic, fingerprint, and DNS changes between smoke-check runs.
- [x] **DNS Resolution Appendix & Host Mapping**: Smoke-check reports now capture resolved addresses for frontend, API, geo, and websocket hosts.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
