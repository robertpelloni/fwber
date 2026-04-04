# PROJECT_STATUS.md - fwber v1.4.8 (Smoke Check Report Artifacts & Live Drift Detection)

**Date:** 2026-04-04
**Version:** 1.4.8 "Smoke Check Report Artifacts & Live Drift Detection"
**Status:** ✅ **VERIFIED, COMMITTED, AND EVEN BETTER PREPARED FOR HETZNER CUTOVER**

---

## 🎯 What This Release Delivered
This release upgraded the new smoke-check automation from a console-only tool into a reusable deployment evidence generator.

Delivered:
- JSON and Markdown smoke-check report artifacts
- deploy-script support for timestamped report directories
- real public smoke-check execution against the currently reachable fwber domains
- concrete detection of live deployment drift affecting health endpoints and geo routing

## 🚀 Operations Improvements
### Extended `ops/hetzner/scripts/smoke-check.sh`
New capabilities:
- `FWBER_REPORT_DIR` for automatic report generation
- `FWBER_REPORT_JSON_PATH` for explicit JSON output path
- `FWBER_REPORT_MD_PATH` for explicit Markdown output path
- case-by-case result recording for passes, warnings, and failures
- machine-readable run summaries suitable for release notes or future Slack/CI integrations

Generated artifacts:
- `smoke-check-summary.json`
- `smoke-check-summary.md`

### Updated `ops/hetzner/scripts/deploy-backend.sh`
When `FWBER_RUN_SMOKE_CHECK=1` is used, the deploy script now creates a timestamped report directory under:
- `logs/deploy-reports/<timestamp>/`

This can be overridden with:
- `FWBER_DEPLOY_REPORT_DIR=/custom/path`

## 🌐 Real Public Validation Findings
I ran the smoke-check script against the currently reachable public deployment targets with report generation enabled.

Observed results:
- frontend reachability: **pass** (`307` redirect)
- invalid-login contract: **pass** (`422`)
- public roast preview: **pass** (`200`)
- `/api/health`: **fail** (`404` route not found)
- `/api/health/liveness`: **fail** (`404` route not found)
- `/api/health/readiness`: **fail** (`404` route not found)
- `geo.fwber.me/nearby`: **fail** (`404` Vercel deployment not found)

## ✅ Why This Matters
This release did more than improve tooling; it surfaced the next concrete live-environment blockers:
1. the pushed health routes are not yet reflected on the reachable `api.fwber.me` deployment
2. the `geo.fwber.me` domain is not currently pointing at a working geo-service deployment

That is exactly the kind of deployment drift the smoke-check/report layer was meant to expose quickly.

## ✅ Validation
- Shell syntax validation passed:
  - `bash -n ops/hetzner/scripts/smoke-check.sh`
  - `bash -n ops/hetzner/scripts/deploy-backend.sh`
- Smoke-check report generation validated end to end with:
  - `FWBER_SKIP_LOCAL_ARTISAN=1 FWBER_SKIP_WEBSOCKET=1 FWBER_REPORT_DIR=<tmp> bash ops/hetzner/scripts/smoke-check.sh`
- JSON and Markdown report files were successfully emitted and inspected
