# TODO — fwber Immediate Action Items

> **Version:** 1.2.1  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Scale & Edge Cases
- [ ] **Native App Deployment Pipeline**: Finalize App Store Connect and Google Play Console automated builds (Fastlane/EAS) so we can distribute the .ipa and .aab.

## 🟡 High: Mobile Polish
- [ ] **Background Fetch Refresh**: Test what happens to the background worker token if the user is logged out of the web view. We need to clear the SecureStore token to prevent ghost pings.

## ✅ Recently Completed
- [x] **Web Worker Photo Hydration**: Offloaded AES-GCM photo decryption to a background thread to prevent UI freezing.
- [x] **Geo-Service Load Testing**: Built the simulation artisan command.
- [x] **Native Permissions UX**: Built a dedicated "Enable Location" splash screen for mobile to improve background permission opt-in rates.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
