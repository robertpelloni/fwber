# PROJECT_STATUS.md - fwber v1.8.27 (Mobile Store Prep)

**Date:** 2026-04-07
**Version:** 1.8.27 "Mobile Store Prep"
**Status:** ✅ **MOBILE SHELL IS CONFIGURED FOR IOS/ANDROID APP STORE DISTRIBUTION**

---

## 🎯 What This Release Delivered
This release closed the gap on the mobile app's native capabilities to prepare for EAS builds and store distribution.

Delivered:
- **Native Push configuration**: Added the `expo-notifications` package and config plugin to ensure native apps can properly request background push permission.
- **Location capabilities**: Added the `expo-location` config plugin to `app.json` with the required privacy string for App Store review.

## ✅ Why This Matters
While the WebView bridge was fully coded in React Native in previous tranches, the underlying Expo plugins were missing from the configuration. Without them, the EAS build process would silently fail to wire up the necessary iOS Entitlements and Android Manifest permissions, preventing push notifications and location from working in the standalone app.
