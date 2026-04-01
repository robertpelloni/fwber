# Project Status — fwber v1.0.22 (Tagged Cache Runtime Fallback)

**Date:** 2026-04-01  
**Version:** 1.0.22 "Tagged Cache Runtime Fallback"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Confirmed `v1.0.21` is fully live on DreamHost and Vercel, including `/api/health` reporting the correct deployed version.
- [x] Hardened the shared tagged-cache fallback so runtime tag failures no longer crash `/api/matches` and related cache-tagged endpoints on DreamHost.
- [x] Added unit coverage for tagged cache runtime fallback behavior and revalidated the match filter feature suites.
- [ ] Awaiting redeploy and live verification that `/api/matches` no longer logs cache-tagging exceptions.
