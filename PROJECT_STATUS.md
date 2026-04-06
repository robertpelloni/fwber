# PROJECT_STATUS.md - fwber v1.8.5 (Rewind Commerce Hub Recovery)

**Date:** 2026-04-06
**Version:** 1.8.5 "Rewind Commerce Hub Recovery"
**Status:** ✅ **RESTORE BRANCH NOW EXPOSES THE MERCHANT / LOCAL-COMMERCE LAYER AS A COHERENT TOP-LEVEL DESTINATION**

---

## 🎯 What This Release Delivered
This release continued the hub-restoration strategy by grouping the branch’s merchant onboarding and business-operation surfaces into one dedicated destination.

Delivered:
- added a dedicated `/commerce` page
- surfaced merchant onboarding, dashboard operations, profile, promotions, analytics, and vibe-broadcast tooling from one coherent destination
- expanded navigation and dashboard cards so the merchant/local-commerce layer is easy to reach from the signed-in shell
- preserved green local production build validation without introducing any backend-risk changes

## ✅ Why This Matters
The rewind branch already had the merchant systems restored enough to function, but they still felt scattered across business-specific routes. The new commerce hub makes that layer legible and intentional, which matters because merchant trust and moderation tooling were part of the broader approved restoration direction and are now easier to evaluate as one product area.
