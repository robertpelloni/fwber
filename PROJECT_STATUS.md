# Project Status — fwber v1.0.13 (Notifications Route Repair)

**Date:** 2026-04-01  
**Version:** 1.0.13 "Notifications Route Repair"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Repaired the backend `/notifications` route conflict causing post-login notification fetch failures.
- [x] Disabled login-page prefetch noise and quieted known low-value websocket connection errors.
- [ ] Awaiting rebuild and production redeploy verification.
