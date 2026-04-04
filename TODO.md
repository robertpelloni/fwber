# TODO — fwber Immediate Action Items

> **Version:** 1.3.0  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Go-to-Market
- [ ] **TestFlight Verification**: Confirm the GitHub Actions / EAS pipeline successfully deposits the `.ipa` into App Store Connect.
- [ ] **Play Console Verification**: Confirm the mobile release workflow successfully lands the `.aab` in Google Play internal testing.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Foreground Notification UX**: Add polished in-app toast handling when pushes arrive while the app is already open so mobile users get contextual feedback without needing to tap the OS notification tray.

## ✅ Recently Completed
- [x] **Trust & Safety Hardening**: Blocking now severs discovery visibility, active matches, and messaging access rather than acting as a superficial UI flag.
- [x] **Frontend Safety Contract Fix**: `fwber-frontend/lib/api/safety.ts` now sends the backend-required `user_id` payload for blocks.
- [x] **Unblock API Exposure**: Registered `DELETE /api/blocks/{userId}` so the existing backend unblock controller is actually reachable.
- [x] **Discovery Stability Cleanup**: Removed active matching-path assumptions about archived `followedTopics` relations and the old `date_feedback` table.
- [x] **Safety Regression Coverage**: Added `tests/Feature/BlockSafetyFlowTest.php` and verified it passes alongside `CoreDatingFlowTest.php`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
