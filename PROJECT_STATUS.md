# Project Status — fwber v1.0.28 (Production Auth Probe Repair)

**Date:** 2026-04-01  
**Version:** 1.0.28 "Production Auth Probe Repair"  
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend Verification Tooling
- **Auth Probe**: `/test-auth` now uses the same live `/api` browser proxy as the real app instead of stale localhost-only URLs.
- **Release Verification**: Production smoke checks can now reuse the built-in auth probe to validate login and protected endpoint access after deploys.
- **Baseline**: Backend production health is already live on `1.0.27`; this patch is a frontend verification/ops repair on top.

## ✅ Release Focus
- [x] Verified `v1.0.27` is live on the backend and public frontend aliases now report the updated release line.
- [x] Traced the broken `/test-auth` production probe to hardcoded localhost backend URLs in the frontend page itself.
- [x] Updated `/test-auth` to use the browser-safe `/api` proxy used by the rest of the deployed frontend auth flow.
- [x] Revalidated the frontend change with `npm run type-check` and a successful production build.
- [ ] Awaiting redeploy and live verification that `/test-auth` now performs a real production login/protected-profile probe instead of failing immediately.
