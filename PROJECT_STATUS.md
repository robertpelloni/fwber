# Project Status — fwber v1.0.24 (Sanctum Token Touch Throttle)

**Date:** 2026-04-01  
**Version:** 1.0.24 "Sanctum Token Touch Throttle"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Confirmed `v1.0.23` is fully live on DreamHost and Vercel, including `/api/health` and all public login aliases reporting the correct deployed version.
- [x] Verified the old notification schema-check slow-query cluster did not reappear after `v1.0.23`; the remaining fresh auth-path slow query came from `personal_access_tokens.last_used_at` updates.
- [x] Registered a custom Sanctum token model that throttles `last_used_at` writes and added regression coverage for first-touch, within-window skip, and post-window refresh behavior.
- [ ] Awaiting redeploy and live verification that bearer-auth slow-query noise drops after the token-touch throttle ships.
