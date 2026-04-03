# TODO — fwber Immediate Action Items

> **Version:** 1.2.1  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Core Experience
- [ ] **Battery-Efficient Background Location**: Complete the OS-level background location tracking implementation in the React Native app. Ensure `Location.startLocationUpdatesAsync` triggers backend updates correctly.
- [ ] **Push Notifications**: Wire up the Firebase Cloud Messaging (FCM) tokens to Laravel notifications to send real-time alerts.

## 🟡 High: Stability & UX
- [ ] **E2E Multi-Device Sync**: Polish the key storage logic to encrypt E2E private keys with a user passphrase and backup to the server.

## ✅ Recently Completed
- [x] **The Great Simplification**: Permanently removed all non-core bloat (Federation, Governance, Economy, Gamification).
- [x] **Database Cleanup**: Dropped 50+ unused tables and foreign key constraints.
- [x] **Routing Polish**: Streamlined `api.php` and `AppHeader.tsx` to strictly serve proximity dating workflows.
- [x] **Sidebar UI Bug**: Fixed the left navigation bar to extend to the bottom of the viewport on all pages.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
