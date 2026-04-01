# Project Status — fwber v1.0.14 (Notifications Payload Hardening)

**Date:** 2026-04-01  
**Version:** 1.0.14 "Notifications Payload Hardening"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Hardened the backend `/notifications` endpoint against malformed stored notification payloads.
- [x] Added a regression test to keep bad notification rows from crashing authenticated fetches again.
- [ ] Awaiting backend deployment and live verification.
