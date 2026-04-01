# Project Status — fwber v1.0.30 (Vercel Trigger Script LF Fix)

**Date:** 2026-04-01  
**Version:** 1.0.30 "Vercel Trigger Script LF Fix"
**Status:** 🚀 **PATCHED AND READY TO REDEPLOY**

---

## 🌐 Frontend Rollout Recovery
- **Root Cause**: The frontend-only Vercel retrigger script was checked out with CRLF line endings, which breaks `bash` execution on Linux/Vercel and makes a safe rollout nudge unreliable.
- **Deploy Trigger Repair**: `fwber-frontend/vercel-ignore-build.sh` is now normalized for LF execution, and repository attributes enforce LF for shell scripts going forward.
- **Release Verification**: This restores the repo's safe no-op mechanism for nudging a stalled frontend deployment without changing runtime application behavior.

## ✅ Release Focus
- [x] Confirmed `origin/main` already contains the `v1.0.29` auth-probe fix while public frontend aliases remained stuck on `1.0.28`.
- [x] Identified the frontend rollout recovery mechanism as the real operational bug: `bash` failed on the CRLF-formatted `vercel-ignore-build.sh`.
- [x] Added repository LF enforcement for shell scripts and refreshed the safe frontend-only deploy trigger file.
- [ ] Awaiting redeploy and live verification that public frontend aliases advance beyond `1.0.28` and serve the `/test-auth` page with empty fields and operator guidance.
