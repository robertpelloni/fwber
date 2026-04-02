# Project Status — fwber v1.0.41 (Merchant Broadcast History)

**Date:** 2026-04-02  
**Version:** 1.0.41 "Merchant Broadcast History"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Merchant Broadcast History
- **Analytics History Feed**: `/api/merchant-portal/analytics` now returns recent `merchant_pulse_broadcast` history alongside the existing KPI, retention, and promotion metrics payload.
- **Pulse Audit Trail**: Merchants can now see each recent broadcast's content, associated promotion, vibe target, live vibe snapshot, promo code, visibility radius, send time, and expiry status.
- **Single Fetch Contract**: The analytics page keeps using one API request instead of bolting on a second history endpoint, so the merchant portal stays coherent and easy to maintain.
- **Header Overlap Polish**: The dashboard now suppresses the floating global subpage nav and uses a roomier app-header logo/badge layout so the hover-logo area no longer crowds the top bar.

## ✅ Release Focus
- [x] Extended merchant analytics with recent broadcast history sourced from `merchant_pulse_broadcast` proximity artifacts.
- [x] Added backend regression coverage for broadcast history aggregation and the analytics endpoint payload.
- [x] Added a merchant analytics history panel that surfaces recent sends, vibe snapshots, promo codes, radius, and expiry status.
- [x] Polished the dashboard header spacing by suppressing the floating fallback nav there and shifting the app-header status cluster away from the logo.
- [x] Revalidated backend coverage with `php artisan test tests/Feature/MerchantAnalyticsTest.php tests/Feature/MerchantPulseTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
