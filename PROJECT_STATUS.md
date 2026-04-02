# Project Status — fwber v1.0.37 (Merchant Vibe Contract Fix)

**Date:** 2026-04-02  
**Version:** 1.0.37 "Merchant Vibe Contract Fix"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Merchant Vibe Contract Fix
- **Pulse Location Fallback**: Merchant vibe analysis now uses the merchant's newest mapped promotion as the owned location source when the merchant profile lacks persisted coordinates.
- **Frontend Contract Alignment**: `NeighborhoodVibe` now consumes the shared API helper's unwrapped response shape correctly and surfaces explicit fallback messaging when no vibe location is available yet.
- **Operator Guidance**: Merchants now get a clear explanation that they need at least one mapped promotion before local vibe analysis can run.
- **Regression Coverage**: Merchant pulse tests now cover both promotion-backed vibe analysis and the explicit missing-location failure path.

## ✅ Release Focus
- [x] Fixed the merchant pulse backend location source mismatch.
- [x] Fixed the merchant vibe widget response-shape mismatch.
- [x] Added merchant pulse regression coverage.
- [x] Revalidated backend merchant coverage with `php artisan test tests/Feature/MerchantPulseTest.php tests/Feature/MerchantPromotionCrudTest.php tests/Feature/MerchantAnalyticsTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
- [x] Revalidated the frontend with `npm run build`.
