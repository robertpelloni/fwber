# PROJECT_STATUS.md - fwber v1.6.8 (NodeInfo 500 Recovery + Frontend CI Runtime Fix)

**Date:** 2026-04-05
**Version:** 1.6.8 "NodeInfo 500 Recovery + Frontend CI Runtime Fix"
**Status:** ✅ **PUBLIC DISCOVERY 500 ROOT-CAUSED; FRONTEND CI RUNTIME ALIGNMENT STAGED**

---

## 🎯 What This Release Delivered
This release clears another live production 500 and stages the frontend CI runtime fix.

Delivered:
- `NodeInfoController` now degrades cleanly when federation-era columns are absent
- public discovery endpoints are covered against minimal-schema drift
- frontend GitHub build workflow is upgraded to Node.js 24

## ✅ Why This Matters
Live production inspection showed `/nodeinfo/2.0` was still 500ing due to a missing `is_federated` column. That is now fixed in source and covered by tests.
