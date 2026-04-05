# PROJECT_STATUS.md - fwber v1.6.5 (Hetzner Backend Stability Repair)

**Date:** 2026-04-04
**Version:** 1.6.5 "Hetzner Backend Stability Repair"
**Status:** ✅ **LOCAL BACKEND REPAIR PATCH VALIDATED; HETZNER DEPLOY + LIVE RE-VERIFICATION NEXT**

---

## 🎯 What This Release Delivered
This release focuses on repairing concrete live Hetzner backend breakage discovered during direct production inspection.

Delivered:
- replaced the broken backend root route that referenced a non-existent `welcome` view
- added the missing `WebFingerController` so public discovery routes no longer break route tooling / route cache evaluation
- hardened dashboard stats and activity so they return safe zero/empty values when `user_matches` is absent in a drifted production database
- fixed the PHP 8.4 `limit` type bug in dashboard activity
- added a corrective migration to restore `user_matches` / `match_actions` if migration history and live schema drift diverge
- hardened backend log-file permissions and the Hetzner deploy script against next-day Monolog rotation ownership drift
- added regression tests covering degraded-schema dashboard behavior and repaired public web routes

## ✅ Why This Matters
Direct Hetzner inspection showed the backend infrastructure itself was alive, but application consistency had drifted:
- `api.fwber.me/` returned `500` because the route rendered a non-existent `welcome` view
- dashboard activity calls were throwing because `user_matches` was missing from the live MySQL schema even though migrations claimed it had already run
- `php artisan route:list` was broken by a route pointing at a missing `WebFingerController`

This release converts those findings into tracked, tested source changes instead of leaving them as one-off server mysteries.
