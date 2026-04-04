# PROJECT_STATUS.md - fwber v1.3.1 (Foreground Notification UX)

**Date:** 2026-04-04
**Version:** 1.3.1 "Foreground Notification UX"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🔔 Native-to-Web Foreground Push Bridge
This release focused on the last obvious gap in the mobile notification experience: **what happens when a push arrives while the app is already open**.

Previously:
- Expo received foreground pushes in `mobile/app/index.js`
- the app logged them to the native console
- but the user saw nothing inside the WebView unless they later checked the OS tray

Now:
- the Expo shell converts foreground notifications into a browser `CustomEvent`
- the WebView dispatches `fwber:native-notification`
- the Next.js app consumes that event and renders an in-app toast immediately
- message and match pushes include direct actions that route the user into the correct page

## ✅ UX Improvements Delivered
- **Foreground push toasts:** open mobile sessions now receive immediate visual feedback for new matches and new messages.
- **Actionable toasts:** taps on the toast CTA route directly to `/matches` or `/messages` without forcing the user through the notification tray.
- **Cold-start routing guard:** the native shell now checks `Notifications.getLastNotificationResponseAsync()` on launch so notification opens remain reliable when the app was resumed from a push.
- **Shared toast system reuse:** the native bridge feeds the existing `ToastProvider`, keeping the UX consistent with the rest of the app instead of inventing a second notification UI.

## ✅ Validation
- **Frontend production build succeeded:**
  - `npm run build --prefix fwber-frontend`
- Notes:
  - build completed successfully
  - Sentry emitted existing configuration deprecation warnings, but they did not block compilation

## ✅ Release Focus
- [x] Bridge native foreground pushes into the web UI.
- [x] Show actionable match/message toasts during active mobile sessions.
- [x] Preserve deep-link routing when the app launches from a tapped push.
- [x] Re-verify the frontend production build after the bridge landed.
