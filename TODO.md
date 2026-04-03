# TODO — fwber Immediate Action Items

> **Version:** 1.2.8  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Go-to-Market
- [ ] **TestFlight Verification**: Ensure the GitHub Actions CI/CD pipeline pushes the `.ipa` directly to App Store Connect.
- [ ] **Play Console Verification**: Ensure the `.aab` lands in Google Play internal testing rails.

## 🟡 High: Feature Polish
- [ ] **Asset Production**: Have the design team execute the screenshot blueprints outlined in `mobile/STORE_ASSETS.md` to finalize the native store listings.

## ✅ Recently Completed
- [x] **Native Push Deep Links**: Engineered `mobile/app/index.js` to parse push notification JSON payloads and directly route the WebView to the correct chat or match.
- [x] **Global E2E Recovery UX**: Moved the key recovery alert out of the dashboard and into a persistent `<E2ERecoveryAlert />` inside the root `<ProtectedRoute />`.
- [x] **CI/CD Automation**: Deployed GitHub Actions for automated backend tests, frontend Next.js builds, and Expo/EAS native mobile deployments.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
