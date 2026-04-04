# TODO — fwber Immediate Action Items

> **Version:** 1.5.3
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Hetzner VPS Provisioning Execution**: Complete server creation, DNS cutover, package install, env setup, and service configuration using the deployment docs plus `ops/hetzner/` templates.
- [ ] **Redeploy After Restoration Phases**: Re-run deployment now that AI, premium/billing, marketplace/merchant, geo-aware merchant discovery, merchant trust scoring, merchant review prioritization, deployment health verification, smoke-check automation, report artifacts, remediation diagnostics, endpoint fingerprinting, DNS appendix reporting, drift diffing, and notification publishing are restored on top of the migration-hardening work.
- [ ] **Fix Live Health Route Drift**: The current reachable `api.fwber.me` deployment still returns `404` for `/api/health`, `/api/health/liveness`, and `/api/health/readiness`; current smoke data shows this traffic resolves to and is served from **Apache at `75.119.202.57`**.
- [ ] **Fix Live Geo Domain Drift**: The current reachable `geo.fwber.me/nearby` contract returns a Vercel deployment-not-found `404`; smoke data now shows `geo.fwber.me` resolves to **`216.198.79.65|64.29.17.1`** and responds via **Vercel at `64.29.17.1`** instead of a geo microservice host.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in an authenticated deployment environment with real secrets.
- [ ] **Provision Smoke-Test Tokens/Keys**: Create or document production-safe smoke-test accounts/tokens plus the Reverb app key path so `ops/hetzner/scripts/smoke-check.sh` can run its optional authenticated and websocket probes in production.
- [ ] **Wire Chat/Webhook Notifications**: Provide a real `FWBER_SMOKE_NOTIFY_WEBHOOK_URL` target if you want deploy notifications delivered automatically outside the report directory.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **Smoke Report Notification Publisher**: Added compact notification artifact generation and optional webhook publishing for smoke + drift report summaries.
- [x] **Smoke Report Drift Diff**: Added comparison tooling that highlights summary, diagnostic, fingerprint, and DNS changes between smoke-check runs.
- [x] **DNS Resolution Appendix & Host Mapping**: Smoke-check reports now capture resolved addresses for frontend, API, geo, and websocket hosts.
- [x] **Endpoint Fingerprints & Host Signals**: Smoke-check reports now capture remote IPs, server headers, redirect targets, content types, and body excerpts for each endpoint.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
