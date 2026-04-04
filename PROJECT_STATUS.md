# PROJECT_STATUS.md - fwber v1.5.3 (Smoke Report Notification Publisher)

**Date:** 2026-04-04
**Version:** 1.5.3 "Smoke Report Notification Publisher"
**Status:** ✅ **VERIFIED, COMMITTED, AND BETTER PREPARED FOR OPERATOR ALERTING**

---

## 🎯 What This Release Delivered
This release extended the deployment evidence system with compact publishable summaries.

Delivered:
- a smoke-report notification publisher
- local notification JSON/Markdown artifacts
- optional webhook publishing support
- deploy-script integration for notification artifact generation after smoke/diff creation

## 🚀 Operations Improvements
### Added `ops/hetzner/scripts/publish-smoke-report.py`
This script consumes:
- `smoke-check-summary.json`
- optional `smoke-check-drift.json`

It produces:
- `smoke-check-notification.json`
- `smoke-check-notification.md`

It can also optionally POST the payload to a webhook via:
- `FWBER_SMOKE_NOTIFY_WEBHOOK_URL`

### Updated `ops/hetzner/scripts/deploy-backend.sh`
When smoke checks run and Python is available, the deploy flow now also generates compact notification artifacts in the current report directory.

## ✅ Validation
- `bash -n ops/hetzner/scripts/deploy-backend.sh`
- `python3 ops/hetzner/scripts/publish-smoke-report.py --help`
- generated smoke summary + drift diff + notification artifacts end to end
- validated JSON and Markdown notification outputs

## ✅ Why This Matters
The deployment-hardening system now supports:
1. detailed evidence
2. drift comparison
3. concise publishable summaries

That makes it easier to wire deploy results into chatops, incident threads, or release notes without manually summarizing large report bundles.
