# Project Status — fwber v1.0.25 (Recommendations Feed Schema Repair)

**Date:** 2026-04-01  
**Version:** 1.0.25 "Recommendations Feed Schema Repair"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Verified `v1.0.24` is fully live on DreamHost and Vercel, with all public login aliases reporting the new version and no fresh `personal_access_tokens.last_used_at` slow query after rollout.
- [x] Traced current recommendation feed failures to a live schema mismatch where recommendation engagement scoring queried `events.start_time` even though the production schema exposes `events.starts_at`.
- [x] Fixed frontend subscription and match pages that were still treating the shared API client as if it returned nested `data` payloads, preventing client-side crashes when live endpoints return normal unwrapped JSON.
- [ ] Awaiting redeploy and live verification that `/api/recommendations/feed` and the affected frontend pages recover cleanly on production.
