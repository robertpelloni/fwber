# PROJECT_STATUS.md - fwber v1.6.6 (Hetzner Log ACL Deploy Fix)

**Date:** 2026-04-05
**Version:** 1.6.6 "Hetzner Log ACL Deploy Fix"
**Status:** ✅ **LIVE LOG-ROTATION DEPLOY CONFLICT PATCHED AT SOURCE AND ON SERVER**

---

## 🎯 What This Release Delivered
This release fixes the last deploy blocker uncovered while applying the backend stability repair.

Delivered:
- removed the Monolog permission override that caused chmod failures on `www-data`-owned daily logs during deploys
- switched the deploy script to ACL-based shared log access for `deploy` + `www-data`
- applied the same ACL repair directly on the live Hetzner server

## ✅ Why This Matters
The previous backend patch revealed that deploy-user artisan commands and PHP-FPM daily log rotation were fighting over ownership semantics. ACLs are the correct shared-write fix here; per-file chmod from an unprivileged deploy shell was not.
