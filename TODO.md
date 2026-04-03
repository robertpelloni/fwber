# TODO — fwber Immediate Action Items

> **Version:** 1.2.1  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Marketing & Launch
- [ ] **Store Assets**: Generate App Store and Google Play Store screenshots for the new "Great Simplification" core.

## 🟡 High: Feature Polish
- [ ] **TestFlight Distribution**: Push the compiled `.ipa` out to beta testers to catch final edge cases before store review.

## ✅ Recently Completed
- [x] **Data Wiping Verification**: Ensured all user media files (including Vault photos and messages) are explicitly wiped from S3/Storage *before* the user record is cascaded.
- [x] **Native App Deployment Pipeline**: Finalized `mobile/fastlane/Fastfile` to automate `eas build` logic and push to TestFlight / Play Console.
- [x] **Web Worker Photo Hydration**: Integrated the `E2EImage` decryption worker directly into the `RealTimeChat.tsx` view for smooth media.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
