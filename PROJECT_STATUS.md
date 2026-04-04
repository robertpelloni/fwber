# PROJECT_STATUS.md - fwber v1.5.0 (Endpoint Fingerprints & Host Signals)

**Date:** 2026-04-04
**Version:** 1.5.0 "Endpoint Fingerprints & Host Signals"
**Status:** ✅ **VERIFIED, COMMITTED, AND PROVIDING STRONGER LIVE ROUTING EVIDENCE**

---

## 🎯 What This Release Delivered
This release upgraded the smoke-check report system again by adding endpoint-level fingerprints for every HTTP probe.

Delivered:
- remote IP capture per checked endpoint
- `Server` header capture per checked endpoint
- redirect/content-type/effective-URL capture
- endpoint fingerprint sections in JSON and Markdown smoke reports
- stronger evidence for distinguishing Apache-served backend drift from Vercel-served geo-domain drift

## 🚀 Operations Improvements
### Extended `ops/hetzner/scripts/smoke-check.sh`
Smoke reports now preserve per-endpoint fingerprints including:
- HTTP method
- requested URL
- HTTP status
- remote IP
- effective URL
- `Server` header
- `Content-Type`
- `Location` header
- body excerpt

### Report output improvements
- `smoke-check-summary.json` now includes a `snapshots` array
- `smoke-check-summary.md` now includes an `Endpoint Fingerprints` table

## 🌐 Real Public Validation Findings (Now More Concrete)
The latest live smoke-check run confirms:
- `fwber.me` responds via **Vercel** and redirects to `https://www.fwber.me/`
- `api.fwber.me` responds via **Apache** at remote IP **`75.119.202.57`** but still does not expose `/api/health*`
- `geo.fwber.me` responds via **Vercel** at remote IP **`64.29.17.1`** and still returns deployment-not-found for the geo endpoint

## ✅ Validation
- `bash -n ops/hetzner/scripts/smoke-check.sh`
- live smoke-check execution with report artifacts enabled
- JSON snapshot inspection
- Markdown endpoint-fingerprint inspection

## ✅ Why This Matters
The deployment-hardening loop is now producing not just pass/fail results and remediation hints, but low-level routing fingerprints that make live-environment debugging much faster.

This materially improves confidence in the current diagnosis:
- backend health-route issue = likely stale/old Apache-served backend rollout
- geo-domain issue = likely Vercel/DNS misrouting rather than Hetzner geo-service behavior
