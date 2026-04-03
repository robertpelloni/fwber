# TODO — fwber Immediate Action Items

> **Version:** 1.2.1  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Load Testing & Security
- [ ] **Data Wiping Verification**: Audit the `anonymizeUserData` function to ensure all S3 objects (Vault media) are actually deleted when an account is scrubbed, not just the DB rows.

## 🟡 High: Feature Polish
- [ ] **Store Assets**: Generate App Store and Google Play Store screenshots for the new "Great Simplification" core.

## ✅ Recently Completed
- [x] **Native App Deployment Pipeline**: Finalized `mobile/fastlane/Fastfile` to automate `eas build` logic and push to TestFlight / Play Console.
- [x] **Web Worker Photo Hydration**: Integrated the `E2EImage` decryption worker directly into the `RealTimeChat.tsx` view for smooth media.
- [x] **Ghost Ping Cleanup**: Background worker tokens are cleared and location tracking stops when a user logs out.
- [x] **EAS Build Pipelines**: Configured Expo Application Services (`eas.json`).

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
