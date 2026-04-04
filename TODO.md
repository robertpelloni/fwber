# TODO — fwber Immediate Action Items

> **Version:** 1.3.1  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Go-to-Market
- [ ] **TestFlight Verification**: Confirm the GitHub Actions / EAS pipeline successfully deposits the `.ipa` into App Store Connect.
- [ ] **Play Console Verification**: Confirm the mobile release workflow successfully lands the `.aab` in Google Play internal testing.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Notification Read/Route Audit**: Verify that in-app toast actions, notification bell links, and native push payload URLs all converge on the same destination logic for messages and matches.

## ✅ Recently Completed
- [x] **Foreground Notification UX**: Native Expo foreground pushes now dispatch a WebView event that the Next.js app turns into immediate in-app toasts.
- [x] **Launch Routing Guard**: The mobile shell now checks the last tapped notification during startup so cold-launch routing stays consistent.
- [x] **Trust & Safety Hardening**: Blocking now severs discovery visibility, active matches, and messaging access rather than acting as a superficial UI flag.
- [x] **Frontend Safety Contract Fix**: `fwber-frontend/lib/api/safety.ts` now sends the backend-required `user_id` payload for blocks.
- [x] **Unblock API Exposure**: Registered `DELETE /api/blocks/{userId}` so the existing backend unblock controller is actually reachable.
- [x] **Safety Regression Coverage**: Added `tests/Feature/BlockSafetyFlowTest.php` and verified it passes alongside `CoreDatingFlowTest.php`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
