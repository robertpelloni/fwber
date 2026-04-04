# PROJECT_STATUS.md - fwber v1.5.9 (Live Dashboard API + Realtime Recovery)

**Date:** 2026-04-04
**Version:** 1.5.9 "Live Dashboard API + Realtime Recovery"
**Status:** ✅ **LIVE FRONTEND CONTRACT DRIFT PATCHED FOR API + REALTIME**

---

## 🎯 What This Release Delivered
This release fixes the exact live issues reported from the signed-in app shell.

Delivered:
- browser API calls now target the Hetzner backend instead of nonexistent Vercel-relative `/api/*` routes
- missing dashboard stats/activity backend routes restored
- realtime client defaults hardened for live `fwber.me` + `ws.fwber.me`
- dashboard endpoints covered by backend tests

## 🚀 User-Facing Impact
This specifically addresses the observed live failures:
- `GET https://www.fwber.me/api/dashboard/stats` → 404
- `GET https://www.fwber.me/api/dashboard/activity` → 404
- `GET https://www.fwber.me/api/security/keys/restore?...` → 404
- persistent disconnected realtime badge when frontend env drift prevents correct Reverb wiring

## ✅ Validation
Validated with:
- `php artisan test --filter=DashboardEndpointsTest`
- `npm run build --prefix fwber-frontend`

Both passed successfully.
