# Project Status — fwber v1.0.15 (Notifications Legacy Schema Compatibility)

**Date:** 2026-04-01  
**Version:** 1.0.15 "Notifications Legacy Schema Compatibility"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Added compatibility for DreamHost's legacy `notifications` table schema used in live production.
- [x] Kept malformed-payload hardening and expanded regression coverage for both schema paths.
- [ ] Awaiting backend redeploy and live verification.
