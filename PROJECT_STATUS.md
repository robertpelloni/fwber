# PROJECT_STATUS.md - fwber v1.2.4 (Ghost Pings & Build Pipelines)

**Date:** 2026-04-04
**Version:** 1.2.4 "Ghost Pings & Build Pipelines"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 👻 Ghost Ping Exorcism
- **Token Invalidation:** Added bidirectional communication between the Next.js `auth-context` and the React Native WebView. When a user logs out in the web UI, a `CLEAR_AUTH_TOKEN` message is broadcasted to the native bridge.
- **Background Task Cleanup:** The mobile app now catches the logout message, purges the JWT from `SecureStore`, and actively calls `Location.stopLocationUpdatesAsync()`. This completely eliminates 401 ghost pings and saves user battery life.

## 🚀 EAS Build Pipelines
- **Fastlane & EAS Prepared:** Created `eas.json` with `development`, `preview`, and `production` profiles.
- **Native Capabilities Configured:** Updated `app.json` with the required `expo-location` and `expo-notifications` plugins, embedding the necessary permission justification strings (`NSLocationAlwaysAndWhenInUseUsageDescription`, etc.) directly into the build pipeline for App Store compliance.

## ✅ Release Focus
- [x] Eliminate Ghost Pings on logout.
- [x] Configure EAS build pipelines (`eas.json`).
- [x] Verify Expo native plugins in `app.json`.
- [x] Finalize 100% Green test suite.
