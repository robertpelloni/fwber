# TODO — fwber Immediate Action Items

> **Version:** 1.3.9  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Restoration Roadmap
- [ ] **Phase D Restore — Marketplace / Merchant**: Restore merchant dashboard, marketplace route surface, inventory/payment plumbing, and storefront UI now that premium/payment primitives are active again.
- [ ] **Redeploy After Restoration Phases**: Re-run deployment now that AI and premium/billing surfaces are restored on top of the migration-hardening work.
- [ ] **Production Stripe Verification**: Confirm live Stripe purchase + webhook flow in an authenticated deployment environment with real secrets.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **Premium & Billing Restoration**: Restored `PremiumController`, `StripeWebhookController`, `Payment`, `Subscription`, payment/subscription migrations, `/premium`, `/settings/subscription`, `/premium/success`, and the repaired who-likes-you premium flow.
- [x] **AI Surface Restoration**: Restored `AiWingmanController`, `ViralContent`, the `viral_contents` schema, the public `/roast` page, and homepage/share re-entry into the roast flow.
- [x] **Restoration Foundation**: Restored `AiServiceProvider` and `PaymentServiceProvider` to the active backend and re-registered them in Laravel bootstrap/app config.
- [x] **Migration Column Guards**: `optimize_core_indexes` now skips index creation when required columns are missing on drifted deploy targets.
- [x] **Deployment Migration Idempotency**: `2026_04_03_212041_optimize_core_indexes.php` already skips indexes that exist, preventing duplicate-key failures on redeploy.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
