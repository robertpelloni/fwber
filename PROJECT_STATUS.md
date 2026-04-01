# Project Status — fwber v1.0.16 (Auth Restore And Legacy Notifications Stability)

**Date:** 2026-04-01  
**Version:** 1.0.16 "Auth Restore And Legacy Notifications Stability"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Prevented transient frontend auth-restore failures from clearing valid sessions during boot.
- [x] Deferred protected photo/notification requests until auth initialization completes.
- [x] Completed legacy DreamHost notifications support for count and read mutations.
- [ ] Awaiting live rollout verification for the combined auth and notifications hotfix.
