# TODO — fwber Immediate Action Items

> **Version:** 1.3.4  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Go-to-Market
- [ ] **TestFlight Verification**: Confirm the GitHub Actions / EAS pipeline successfully deposits the `.ipa` into App Store Connect.
- [ ] **Play Console Verification**: Confirm the mobile release workflow successfully lands the `.aab` in Google Play internal testing.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Real-Device Notification QA**: Verify foreground, background, and cold-start notification flows on physical iOS/Android devices now that routes and toasts are standardized.
- [ ] **Production Login 500 Root Cause Audit**: Inspect live backend logs for the production `/api/auth/login` 500 so the server-side failure itself is fixed, not just the frontend error handling.

## ✅ Recently Completed
- [x] **Console Error Sweep**: Removed stale retired-route prefetches, restored analytics event ingestion routing, and hardened auth parsing against malformed server responses.
- [x] **Notification Settings Contract Fix**: Backend now exposes `PUT /api/notification-preferences/{type}` to match the frontend API client.
- [x] **Sentry Build Modernization**: Replaced deprecated client/instrumentation patterns and removed Sentry-specific frontend build warnings.
- [x] **Notification Route Consistency**: Standardized backend notification payloads and unified toast/bell route logic through `fwber-frontend/lib/notifications.ts`.
- [x] **Trust & Safety Hardening**: Blocking now severs discovery visibility, active matches, and messaging access rather than acting as a superficial UI flag.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
