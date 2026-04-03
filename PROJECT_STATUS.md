# PROJECT_STATUS.md - fwber v1.2.9 (Go-To-Market Polish & E2E UX)

**Date:** 2026-04-04
**Version:** 1.2.9 "Go-To-Market Polish & E2E UX"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🔔 Native Push Routing
- **Deep Linking:** Overhauled the React Native `mobile/app/index.js` wrapper to explicitly hook into the `expo-notifications` response listeners. When a user taps a background push notification (e.g. "New Match!"), the mobile shell automatically intercepts the attached URL payload and seamlessly routes the internal `WebView` to the correct chat or match screen.

## 🔐 Global E2E Key Recovery UX
- **Persistent Alerting:** Removed the easy-to-miss dashboard notification and constructed a global, animated `<E2ERecoveryAlert />` component.
- **Data Safety:** This alert is now permanently injected into the root `<ProtectedRoute />` wrapper. If a user logs into a new device (where local IndexedDB E2E keys are missing) but a remote backup is detected, they are continuously prompted to recover their keys, ensuring zero loss of chat history or media across device upgrades.

## ✅ Release Focus
- [x] Wire up Native Push Notification deep links in Expo.
- [x] Build global `<E2ERecoveryAlert />`.
- [x] Clean up redundant Dashboard code.
