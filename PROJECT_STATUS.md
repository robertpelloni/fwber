# Project Status — fwber v1.0.33 (Merchant Contract Repair)

**Date:** 2026-04-02  
**Version:** 1.0.33 "Merchant Contract Repair"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Merchant Portal Contract And Verification Polish
- **Merchant API Alignment**: The frontend merchant types and helpers now match the backend's real `verification_status`, `lat`/`lng`, promo-code, and response payload shapes.
- **Merchant Analytics Repair**: The analytics page now consumes the shared API client correctly and renders the backend's `kpis`, `retention`, and promotion performance payloads.
- **Profile And Verification UX**: Merchants now have a dedicated profile page, visible verification status on the dashboard, and navigation to profile management from the shared merchant header.
- **Onboarding Accuracy**: Merchant registration no longer submits unsupported `phone` and `website` fields, and merchant onboarding/promotions copy now reflects the verification gate before promotions can go live.

## ✅ Release Focus
- [x] Repaired merchant frontend/backend contract mismatches that were breaking profile, analytics, and promotion flows.
- [x] Added merchant profile management and surfaced verification state in the merchant UI.
- [x] Corrected merchant onboarding and promotion creation flows to match live backend validation and gating.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run build`.
