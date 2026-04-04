# PROJECT_STATUS.md - fwber v1.5.7 (Hetzner Script Executable Bits)

**Date:** 2026-04-04
**Version:** 1.5.7 "Hetzner Script Executable Bits"
**Status:** ✅ **LIVE HETZNER CUTOVER STABILIZATION CONTINUES**

---

## 🎯 What This Release Delivered
This release fixes an operational packaging issue discovered on the live Hetzner server.

Delivered:
- Hetzner ops scripts are now tracked as executable in git
- fresh clones/pulls retain runnable permissions for deploy/smoke/diff/publish scripts

## 🚀 Operations Improvements
Updated git metadata for:
- `ops/hetzner/scripts/deploy-backend.sh`
- `ops/hetzner/scripts/smoke-check.sh`
- `ops/hetzner/scripts/compare-smoke-reports.py`
- `ops/hetzner/scripts/publish-smoke-report.py`

## ✅ Why This Matters
During live Hetzner execution, a pulled repo could contain the correct scripts but still skip smoke execution because the deploy script gated on `-x` while the repo tracked the files as non-executable.

This release removes that packaging-level footgun and makes future server pulls behave more predictably.
