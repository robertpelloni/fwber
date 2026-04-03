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
- [x] **CI/CD Automation**: Deployed GitHub Actions for automated backend tests, frontend Next.js builds, and Expo/EAS native mobile deployments.
- [x] **Database Scale Optimization**: Executed `optimize_core_indexes` migration to heavily index spatial lookups, unread message counts, and match filtering fields for O(1) retrieval speeds.
- [x] **Store Asset Specifications**: Authored `STORE_ASSETS.md` defining exactly what UI views to screenshot for App Store optimization.
- [x] **Data Wiping Verification**: Ensured all user media files (including Vault photos and messages) are explicitly wiped from S3/Storage *before* the user record is cascaded.
- [x] **Native App Deployment Pipeline**: Finalized `mobile/fastlane/Fastfile` to automate `eas build` logic.
- [x] **Web Worker Photo Hydration**: Integrated the `E2EImage` decryption worker directly into the `RealTimeChat.tsx` view for smooth media.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
