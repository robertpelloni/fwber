# Project Status — fwber v1.0.38 (Merchant Pulse Broadcast Activation)

**Date:** 2026-04-02  
**Version:** 1.0.38 "Merchant Pulse Broadcast Activation"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Merchant Pulse Broadcast Activation
- **Real Broadcast Path**: Merchant pulse broadcasts now create real proximity `announce` artifacts using the merchant's latest mapped promotion as the owned location source.
- **Token Economy Wiring**: Successful broadcasts now spend 50 FWB Tokens atomically and return the merchant's remaining balance in the API response.
- **Honest Vibe Gating**: Vibe-targeted sends now compare the live neighborhood vibe against the merchant trigger and reject mismatches without charging tokens or creating artifacts.
- **Merchant UI Alignment**: The merchant vibe deck now describes the real behavior, reports the live vibe on failure, and confirms immediate sends instead of claiming a fake queued state.
- **Regression Coverage**: Merchant pulse tests now cover successful sends, vibe mismatch rejection, and the missing-location path in addition to the existing vibe lookup behavior.

## ✅ Release Focus
- [x] Replaced the merchant pulse broadcast stub with real artifact creation and token spending.
- [x] Added live vibe-target rejection without token spend on mismatches.
- [x] Updated the merchant vibe page copy and error handling to match the real API contract.
- [x] Added merchant pulse regression coverage for broadcast send, block, and missing-location cases.
- [x] Revalidated backend merchant coverage with `php artisan test tests/Feature/MerchantPulseTest.php tests/Feature/MerchantPromotionCrudTest.php tests/Feature/MerchantAnalyticsTest.php`.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
- [x] Revalidated the frontend with `npm run build`.
