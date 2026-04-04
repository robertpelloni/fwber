# TODO — fwber Immediate Action Items

> **Version:** 1.3.2  
> **Last Updated:** 2026-04-04

---

## 🔴 Critical: Go-to-Market
- [ ] **TestFlight Verification**: Confirm the GitHub Actions / EAS pipeline successfully deposits the `.ipa` into App Store Connect.
- [ ] **Play Console Verification**: Confirm the mobile release workflow successfully lands the `.aab` in Google Play internal testing.

## 🟡 High: Product Polish
- [ ] **Store Asset Production**: Execute the screenshot and copy plan in `mobile/STORE_ASSETS.md`.
- [ ] **Sentry Next.js Cleanup**: Resolve the current frontend build warnings around outdated Sentry Next.js instrumentation and deprecated client config naming.

## ✅ Recently Completed
- [x] **Notification Route Consistency**: Standardized backend notification payloads and unified toast/bell route logic through `fwber-frontend/lib/notifications.ts`.
- [x] **Conversation-Aware Message Routing**: Message notifications now route into `/messages?user={id}`, and the messages page auto-selects the matching conversation.
- [x] **Foreground Notification UX**: Native Expo foreground pushes now dispatch a WebView event that the Next.js app turns into immediate in-app toasts.
- [x] **Launch Routing Guard**: The mobile shell now checks the last tapped notification during startup so cold-launch routing stays consistent.
- [x] **Trust & Safety Hardening**: Blocking now severs discovery visibility, active matches, and messaging access rather than acting as a superficial UI flag.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
