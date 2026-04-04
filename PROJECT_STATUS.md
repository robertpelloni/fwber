# PROJECT_STATUS.md - fwber v1.4.9 (Smoke Check Diagnostics & Remediation Hints)

**Date:** 2026-04-04
**Version:** 1.4.9 "Smoke Check Diagnostics & Remediation Hints"
**Status:** ✅ **VERIFIED, COMMITTED, AND PRODUCING BETTER DEPLOY TRIAGE SIGNALS**

---

## 🎯 What This Release Delivered
This release upgraded the smoke-check reporting layer again by making it explain likely causes and recommended next actions, not just raw failures.

Delivered:
- structured deployment diagnostics inside smoke-check reports
- remediation guidance for common deploy-drift signatures
- another real public smoke-check run proving the diagnostics work against live fwber domain behavior

## 🚀 Operations Improvements
### Extended `ops/hetzner/scripts/smoke-check.sh`
Reports now include:
- a `diagnostics` array in JSON output
- a `Diagnostics & Recommended Actions` section in Markdown output

Current heuristics cover:
- backend route drift on `api.fwber.me`
- geo domain misrouting / Vercel deployment drift
- incomplete authenticated smoke coverage
- partial-health narrowing hints when some public routes still work

## 🌐 Real Public Validation Findings (Still Current)
The latest public smoke-check run continues to show:
- frontend reachability: **pass** (`307`)
- invalid-login contract: **pass** (`422`)
- public roast preview: **pass** (`200`)
- `/api/health*`: **fail** (`404` route not found)
- `geo.fwber.me/nearby`: **fail** (Vercel deployment-not-found `404`)

The difference now is that the smoke-check reports explicitly translate those failures into recommended next steps.

## ✅ Validation
- `bash -n ops/hetzner/scripts/smoke-check.sh`
- `git diff --check`
- live smoke-check execution with report artifacts enabled
- generated reports inspected to confirm diagnostics were present and correct

## ✅ Why This Matters
This is a practical operator-experience improvement. During a real rollout, it is much more useful for the script to say:
- the backend looks stale
- the geo domain still points at Vercel
- the deployment is partially healthy rather than broadly down

than to leave an operator interpreting raw JSON/HTTP fragments under time pressure.
