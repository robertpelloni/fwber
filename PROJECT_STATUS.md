# PROJECT_STATUS.md - fwber v1.8.25 (Extended Performance Monitoring Pass)

**Date:** 2026-04-07
**Version:** 1.8.25 "Extended Performance Monitoring Pass"
**Status:** ✅ **MAINLINE PERFORMANCE TUNING: MORE N+1 QUERIES ELIMINATED**

---

## 🎯 What This Release Delivered
This release continued the "Performance Monitoring Pass" by targeting additional high-frequency read paths.

Delivered:
- **Match Loop Optimization:** The `MatchController::findMatches` method previously ran an N+1 query loop to calculate the "Proximity Saturation Penalty" for every candidate it found. This was rewritten to pre-fetch aggregate counts via a single `GROUP BY` query.
- **Profile Views Optimization:** The `ProfileViewController::getViews` method fetched user details one by one inside a loop. This was refactored to collect all viewer IDs first and fetch the required data via a batched `whereIn` lookup.

## ✅ Why This Matters
As the restored platform scales, performance tuning shifts from luxury to necessity. Finding and removing N+1 queries from the core matching and discovery paths ensures that the app stays fast without needing to scale up the Hetzner database prematurely.
