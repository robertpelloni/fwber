# PROJECT_STATUS.md - fwber v1.8.20 (Critical Production & CI Repair)

**Date:** 2026-04-07
**Version:** 1.8.20 "Critical Production & CI Repair"
**Status:** ✅ **DASHBOARD 500 ERROR FIXED AND NUCLEAR RECOVERY EXPANDED**

---

## 🎯 What This Release Delivered
This immediate repair release fixed two critical issues introduced in the prior infrastructure pass.

Delivered:
- **Dashboard Fix**: Restored the missing `$daysActive` variable in `DashboardController`, eliminating 500 errors on the main dashboard.
- **Enhanced Nuclear Recovery**: Added `proximity_chatrooms`, `proximity_artifacts`, and their associated comments/votes/messages to the recovery list.
- **Constraint Safety**: Ensured correct table creation order so foreign keys no longer block the recovery process.

## ✅ Why This Matters
The previous release hit a "foreign key wall" because it tried to create comment tables for proximity artifacts that didn't exist yet in the DB. By adding the base proximity tables to the recovery ledger and fixing the dashboard variable, we've cleared the path for a fully successful Hetzner deployment.
