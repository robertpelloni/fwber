# Project Status — fwber v1.0.11 (Auth Persistence Race Fix)

**Date:** 2026-04-01  
**Version:** 1.0.11 "Auth Persistence Race Fix"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Persisted auth tokens immediately when login/register/two-factor flows succeed.
- [x] Removed the race where post-login API calls could see no token yet and force an immediate logout.
- [ ] Awaiting rebuild and production redeploy verification.
