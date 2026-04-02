# Project Status — fwber v1.0.36 (Merchant Promotion Management)

**Date:** 2026-04-02  
**Version:** 1.0.36 "Merchant Promotion Management"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Merchant Promotion Management
- **Promotion CRUD Backend**: Merchants can now fetch a single owned promotion, update its campaign details, and deactivate it through authenticated, ownership-scoped merchant portal endpoints.
- **Promotion Management UI**: `/merchant/promotions/[id]` now exposes campaign metrics, editable schedule/copy controls, and a deactivate action in one dedicated management surface.
- **Merchant Navigation Upgrade**: Merchant dashboard and promotions list cards now deep-link into campaign management, and the merchant header now exposes analytics directly.
- **Shared Merchant API Contract**: The frontend merchant helper now includes promotion detail, update, and delete calls so merchant pages use one consistent contract layer.

## ✅ Release Focus
- [x] Added merchant promotion detail, update, and deactivate endpoints.
- [x] Added merchant ownership regression coverage for the new promotion CRUD flow.
- [x] Added the `/merchant/promotions/[id]` management page.
- [x] Linked dashboard and promotions list surfaces into the new manage flow.
- [x] Added the analytics destination to `MerchantHeader`.
- [x] Revalidated backend merchant coverage with `php artisan test tests/Feature/MerchantPromotionCrudTest.php tests/Feature/MerchantAnalyticsTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
- [x] Revalidated the frontend with `npm run build`.
