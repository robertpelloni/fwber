# Project Status — fwber v1.0.18 (Production Cache And Recommendations Repair)

**Date:** 2026-04-01  
**Version:** 1.0.18 "Production Cache And Recommendations Repair"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Added a safe fallback for tagged-cache reads and invalidation when production cache storage does not support tags.
- [x] Repaired live `subscriptions/history` and `matches` paths to use the safe cache fallback instead of throwing cache-tag exceptions.
- [x] Restored recommendations endpoint compatibility for serialized query params and the frontend's expected response contract.
- [x] Fixed the federation settings page to consume unwrapped API responses safely.
- [ ] Awaiting redeploy and live verification for subscriptions, matches, recommendations, and federation settings.
