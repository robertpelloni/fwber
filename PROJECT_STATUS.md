# Project Status — fwber v1.0.29 (Auth Probe Default Cleanup)

**Date:** 2026-04-01  
**Version:** 1.0.29 "Auth Probe Default Cleanup"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend Verification Tooling
- **Auth Probe Transport**: `/test-auth` already uses the live `/api` browser proxy path introduced in `v1.0.28`.
- **Auth Probe Defaults**: The page no longer preloads stale fake credentials and now explicitly tells operators to use a real production account.
- **Release Verification**: This removes the last misleading false-negative behavior from the built-in production login/profile smoke test.

## ✅ Release Focus
- [x] Verified `v1.0.28` is live and that `/test-auth` now reaches the real production auth API instead of failing on localhost-only URLs.
- [x] Confirmed the remaining `/test-auth` failure mode was stale fake credentials, not broken transport or proxy wiring.
- [x] Removed those misleading defaults and replaced them with explicit manual-production-probe guidance.
- [x] Revalidated the frontend with `npm run type-check` and a successful production build.
- [ ] Awaiting redeploy and live verification that `/test-auth` now opens with empty fields and clear operator guidance on production.
