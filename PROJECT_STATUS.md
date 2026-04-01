# Project Status — fwber v1.0.19 (Cache Fallback Expansion And Notification Schema Caching)

**Date:** 2026-04-01  
**Version:** 1.0.19 "Cache Fallback Expansion And Notification Schema Caching"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Expanded the safe tagged-cache fallback to more live DreamHost runtime paths beyond the original `subscriptions` and `matches` repairs.
- [x] Covered event, profile view, Stripe subscription cache invalidation, cleanup job, and AI matching invalidation paths with the shared fallback.
- [x] Cached legacy notification schema detection to reduce repeated `information_schema` lookups on the notifications hot path.
- [ ] Awaiting redeploy and live verification that no new cache-tag exceptions appear and notification schema-check warnings taper off.
