# PROJECT_STATUS.md - fwber v1.3.2 (Notification Route Consistency)

**Date:** 2026-04-04
**Version:** 1.3.2 "Notification Route Consistency"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🎯 What This Release Solved
This release completed the audit of notification destinations across the product. Before this pass, different notification surfaces were making different routing assumptions:
- native push payloads
- database notifications returned by `/api/notifications`
- notification bell links
- foreground toast CTA actions
- the `/messages` screen itself

That fragmentation creates a subtle but dangerous UX bug class: users tap a notification and land somewhere generic or inconsistent depending on which transport delivered it.

## 🔔 Notification Route Consistency
- **Canonical notification routing helper added:** `fwber-frontend/lib/notifications.ts` now normalizes notification types and computes routes/action labels from shared logic.
- **Backend notification payloads standardized:** `NewMessageNotification` and `NewMatchNotification` now emit consistent `type`, `title`, `body`, `url`, `user_id`, and `user_name` fields for database and push surfaces.
- **Message notifications are now conversation-aware:** message pushes route to `/messages?user={senderId}` instead of dropping users on a generic inbox screen.
- **Messages page now honors notification routing:** `fwber-frontend/app/messages/page.tsx` reads the requested user from the URL and auto-selects the matching conversation when it exists.
- **Notification bell and native toast CTA logic now agree:** both surfaces route through the same helper, reducing drift.

## ✅ Validation
- **Backend tests passed:**
  - `php artisan test tests/Feature/NotificationRoutingTest.php tests/Feature/BlockSafetyFlowTest.php tests/Feature/CoreDatingFlowTest.php`
  - Result: **23 passed**
- **Frontend build passed:**
  - `npm run build --prefix fwber-frontend`
  - Result: successful production build
  - Note: existing Sentry warnings remain non-blocking

## ✅ Release Focus
- [x] Standardize backend notification payload fields.
- [x] Create shared frontend notification route logic.
- [x] Make notification taps open the intended conversation when possible.
- [x] Add backend regression coverage for notification route shape.
- [x] Re-verify backend tests and frontend production build.
