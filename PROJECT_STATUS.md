# Project Status — fwber v1.0.7 (Dashboard Auth Query Cleanup)

**Date:** 2026-04-01  
**Version:** 1.0.7 "Dashboard Auth Query Cleanup"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Removed remaining direct dashboard browser calls to `api.fwber.me`.
- [x] Reduced auth-init request noise from protected widgets.
- [x] Rebuilt successfully after the cleanup.
- [ ] Awaiting production redeploy verification.
