# PROJECT_STATUS.md - fwber v1.5.8 (Restored Feature Navigation Surface)

**Date:** 2026-04-04
**Version:** 1.5.8 "Restored Feature Navigation Surface"
**Status:** ✅ **RESTORED SURFACES ARE NOW EXPOSED IN THE LIVE APP SHELL**

---

## 🎯 What This Release Delivered
This release fixes a product-surface problem rather than a backend/runtime problem.

The restored systems already existed, but they were not obvious in the signed-in experience because they were weakly surfaced in navigation.

Delivered:
- restored-feature links added to the authenticated app shell
- restored-feature cards added to the dashboard
- settings cleaned up to point at real restored surfaces instead of leaving users to guess direct URLs

## 🚀 User-Facing Improvements
Updated:
- `fwber-frontend/components/AppHeader.tsx`
- `fwber-frontend/app/dashboard/page.tsx`
- `fwber-frontend/app/settings/page.tsx`
- `fwber-frontend/lib/auth-context.tsx`

The authenticated UI now exposes:
- Gold Premium
- Profile Roast
- Merchant portal / merchant signup
- Moderation dashboard for moderators

## ✅ Why This Matters
The user report was correct: restored features technically existed, but they did not feel restored because they were not visibly reachable from the main signed-in flow.

This release makes the restoration visible and usable in the actual product shell.
