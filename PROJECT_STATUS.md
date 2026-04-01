# Project Status — fwber v1.0.20 (Notification Schema Memoization)

**Date:** 2026-04-01  
**Version:** 1.0.20 "Notification Schema Memoization"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Confirmed `v1.0.19` is fully live on DreamHost and Vercel after a forced clean frontend redeploy.
- [x] Memoized legacy notification schema detection per request so the notifications hot path no longer repeats the same cache-backed capability check during one response.
- [x] Added regression coverage for the per-request schema memoization path on the legacy notifications schema.
- [ ] Awaiting redeploy and live verification that notification-path slow-query warning noise tapers off further under production traffic.
