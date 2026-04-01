# Project Status — fwber v1.0.12 (Auth Token Source Hardening)

**Date:** 2026-04-01  
**Version:** 1.0.12 "Auth Token Source Hardening"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend (Vercel Edge Network)
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Dashboard**: Stats, leaderboard, activity feed, profile completeness, and boosts now use the shared authenticated API path.
- **Protected Widgets**: Achievements and related widgets now wait for authenticated state before issuing requests.

## ✅ Release Focus
- [x] Added an in-memory auth token source for the shared API client immediately after successful auth flows.
- [x] Kept API-client token state aligned with storage clearing on logout and auth expiry.
- [ ] Awaiting rebuild and production redeploy verification.
