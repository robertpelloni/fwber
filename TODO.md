# TODO — fwber Immediate Action Items

> **Version:** 1.3.8  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Restoration Roadmap
- [ ] **Phase C Restore — Premium/Payments**: Restore premium/payment backend route surface now that payment gateway bindings are active again.
- [ ] **Phase D Restore — Marketplace/Merchant**: Restore merchant/marketplace route surface and UI after premium/payment primitives are active.
- [ ] **Redeploy After Column Guard Fix**: Re-run deployment now that `optimize_core_indexes` skips both pre-existing indexes and missing-column index definitions.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **AI Surface Restoration**: Restored `AiWingmanController`, `ViralContent`, the `viral_contents` schema, the public `/roast` page, and homepage/share re-entry into the roast flow.
- [x] **Restoration Foundation**: Restored `AiServiceProvider` and `PaymentServiceProvider` to the active backend and re-registered them in Laravel bootstrap/app config.
- [x] **Migration Column Guards**: `optimize_core_indexes` now skips index creation when required columns are missing on drifted deploy targets.
- [x] **Deployment Migration Idempotency**: `2026_04_03_212041_optimize_core_indexes.php` already skips indexes that exist, preventing duplicate-key failures on redeploy.
- [x] **Console Error Sweep**: Removed stale retired-route prefetches, restored analytics event ingestion routing, and hardened auth parsing against malformed server responses.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
