# PROJECT_STATUS.md - fwber v1.9.9 (Dashboard Storage Guard + E2E Restore Probe Hardening)

**Date:** 2026-04-05
**Version:** 1.9.9 "Dashboard Storage Guard + E2E Restore Probe Hardening"
**Status:** ✅ **LIVE DASHBOARD STORAGE ERRORS ROOT-CAUSED; FRONTEND NOW DEGRADES SAFELY WHEN BROWSER STORAGE IS BLOCKED**

---

## 🎯 What This Release Delivered
This release targeted the live dashboard/runtime errors reported from production-style usage:
- browser storage access failures are now guarded across auth persistence, API auth-header lookup, and realtime bootstrap
- E2E IndexedDB access is now treated as an optional capability rather than a guaranteed browser primitive
- E2E restore probing no longer hammers the backend when local storage is unavailable in the current browser context
- live verification confirmed the `security/keys/restore` endpoint is present on Hetzner and publicly responds with `401 Unauthenticated`, so the earlier error signature was not caused by the route being missing from the deployed backend

## ✅ Why This Matters
A privacy-first app cannot let blocked browser storage turn into uncaught runtime explosions on the signed-in dashboard. This hardening keeps the product usable in stricter WebView/privacy contexts while preserving E2E recovery behavior where storage is actually available.
