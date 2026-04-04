# TODO — fwber Immediate Action Items

> **Version:** 1.4.2  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Deployment & Verification
- [ ] **Hetzner VPS Provisioning Execution**: Complete server creation, DNS cutover, package install, env setup, and service configuration using the new deployment docs plus `ops/hetzner/` templates.
- [ ] **Redeploy After Restoration Phases**: Re-run deployment now that AI, premium/billing, and marketplace/merchant surfaces are restored on top of the migration-hardening work.
- [ ] **Production Stripe Verification**: Confirm live premium and marketplace purchase + webhook flows in an authenticated deployment environment with real secrets.

## 🟡 High: Product Polish
- [ ] **Geo-Aware Merchant Ranking**: Add real merchant location persistence so nearby marketplace and AR overlays can rank by actual merchant distance.
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.

## ✅ Recently Completed
- [x] **Hetzner Ops Templates & CI Env Alignment**: Added copy-ready Nginx/systemd/scripts under `ops/hetzner/` and corrected frontend workflow/env examples to match the active API + Reverb contract.
- [x] **Hetzner/Vercel Deployment Docs Refresh**: Rewrote active deployment guidance around the new recommended production topology and deprecated DreamHost-first docs.
- [x] **Marketplace & Merchant Restoration**: Restored merchant registration, dashboard, inventory, storefront, analytics, receipts, purchases, redemptions, and compact merchant payment schema.
- [x] **Premium & Billing Restoration**: Restored `PremiumController`, `StripeWebhookController`, `Payment`, `Subscription`, payment/subscription migrations, `/premium`, `/settings/subscription`, `/premium/success`, and the repaired who-likes-you premium flow.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
