# PROJECT_STATUS.md - fwber v1.5.2 (Smoke Report Drift Diff)

**Date:** 2026-04-04
**Version:** 1.5.2 "Smoke Report Drift Diff"
**Status:** ✅ **VERIFIED, COMMITTED, AND PRESERVING INTER-DEPLOY EVIDENCE BETTER**

---

## 🎯 What This Release Delivered
This release extended the deployment evidence system with cross-run comparison.

Delivered:
- a smoke-report diff tool
- drift JSON and Markdown artifacts
- deploy-script integration that compares the newest smoke report against the previous saved one when available

## 🚀 Operations Improvements
### Added `ops/hetzner/scripts/compare-smoke-reports.py`
This script compares two `smoke-check-summary.json` files and generates:
- `smoke-check-drift.json`
- `smoke-check-drift.md`

It compares:
- summary counters
- overall status
- diagnostics
- endpoint fingerprint changes
- DNS changes

### Updated `ops/hetzner/scripts/deploy-backend.sh`
When smoke checks are enabled and a previous smoke report exists, deploys now try to emit drift artifacts into the current report directory.

## ✅ Validation
- `bash -n ops/hetzner/scripts/deploy-backend.sh`
- generated two smoke-check reports locally
- compared them with `compare-smoke-reports.py`
- validated both drift JSON and drift Markdown output

## ✅ Why This Matters
The smoke-check system no longer treats each deploy as an isolated moment. Operators can now answer:
- what changed since the last deploy?
- did any diagnostics resolve?
- did server fingerprints or DNS mappings drift?

That is a practical operational step toward safer Hetzner cutovers and redeploys.
