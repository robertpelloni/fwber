# Project Status — fwber v1.0.4 (Frontend Security Patch)

**Date:** 2026-04-01  
**Version:** 1.0.4 "Frontend Security Patch"  
**Status:** 🚀 **READY TO REDEPLOY WITH PATCHED NEXT.JS**

---

## 🏗️ Architecture (Unified Stack)
The fwber platform remains operational; this release specifically fixes the blocked Vercel deployment by upgrading the frontend off vulnerable Next.js 15.0.3.

### 🌐 Frontend (Vercel Edge Network)
- **Host**: `https://www.fwber.me`
- **Build**: Next.js 15.0.7 / React 18.3.1
- **Routing**: API proxying remains enforced via Next.js rewrites to `https://api.fwber.me/api/:path*`.
- **Release Goal**: Clear Vercel's vulnerable-version gate for CVE-2025-66478.

### 🔌 Backend (DreamHost Shared)
- **Host**: `https://api.fwber.me`
- **Engine**: Laravel 12.44 / PHP 8.2
- **Status**: No backend changes required for this patch release.

## ✅ Current Release Focus
- [x] Root cause confirmed: `origin/main` pinned `fwber-frontend` to `next@15.0.3`.
- [x] Dependency patch applied: frontend now resolves `next@15.0.7`.
- [x] Version sync complete: root and frontend metadata aligned at `1.0.4`.
- [ ] Redeploy pending: push and verify the next Vercel deployment succeeds.

---
*The Pulse of Detroit is patched and ready for a fresh Vercel deployment.*

