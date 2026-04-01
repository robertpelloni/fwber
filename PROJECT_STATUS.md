# Project Status — fwber v1.0.23 (Notification Polling Load Reduction)

**Date:** 2026-04-01  
**Version:** 1.0.23 "Notification Polling Load Reduction"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Confirmed `v1.0.22` is fully live on DreamHost and Vercel, including `/api/health` reporting the correct deployed version and no fresh `/api/matches` tag-support exception after rollout.
- [x] Identified the remaining notification load source as frontend polling that fetched the full `/notifications` payload every 30 seconds from the global header.
- [x] Switched the closed notification bell to the lightweight `/notifications/count` poll while preserving full payload refreshes when the drawer is open.
- [ ] Awaiting redeploy and live verification that notification-path slow-query noise materially drops on production.
