# PROJECT_STATUS.md - fwber v1.9.5 (Restore Branch Smoke Suite + Deploy Hardening Replay)

**Date:** 2026-04-05
**Version:** 1.9.5 "Restore Branch Smoke Suite + Deploy Hardening Replay"
**Status:** ✅ **THE REWIND BRANCH NOW CARRIES THE HEALTH/SMOKE/DEPLOY HARDENING STACK PLUS AN INITIAL LINUX ROUTE-CASE FIX**

---

## 🎯 What This Release Delivered
This release deepened the restore-branch replay beyond basic workflows:
- replayed deployment health endpoints and `deploy:verify`
- replayed smoke-check system, report artifacts, diagnostics, endpoint fingerprints, DNS appendix, drift diffing, and notification publishing
- replayed ACL/logging and nginx-sync deploy hardening
- added a direct restore-branch fix for `Api` namespace casing in Linux route resolution

## ✅ Why This Matters
The rewind branch now contains a large share of the real Hetzner production contract: deploy verification, smoke automation, reporting, and deploy hardening. We also identified and fixed one concrete Linux-only route/controller casing issue that was blocking branch CI. This is exactly the kind of reconciliation work needed to make the full-feature branch operational in the current environment.
