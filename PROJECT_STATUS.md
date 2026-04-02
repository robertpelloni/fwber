# Project Status — fwber v1.0.34 (Federation Trust Polish)

**Date:** 2026-04-02  
**Version:** 1.0.34 "Federation Trust Polish"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Federation Visibility And Trust Polish
- **Real Federation Toggle**: The security settings federation switch now persists through the real profile update API and updates local auth state consistently.
- **Federation Visibility UX**: The federation hub now shows whether your public profile is actually discoverable, and it prevents sharing/follow actions that do not make sense while federation is disabled.
- **Remote Actor Detail**: Search results now include a drill-in modal so users can inspect a remote actor's summary and server before following.
- **Honest Feed Actions**: The global federation feed now labels reply/boost/like controls as read-only upcoming features instead of implying live interactions that do not exist yet.

## ✅ Release Focus
- [x] Fixed the broken federation enable/disable flow so settings no longer misreport federation state.
- [x] Added higher-trust federation onboarding and actor review UX in the settings hub.
- [x] Clarified read-only federation feed actions to match current backend capability.
- [x] Revalidated the frontend with `npm run type-check`.
- [x] Revalidated the frontend with `npm run lint`.
- [x] Revalidated the frontend with `npm run build`.
