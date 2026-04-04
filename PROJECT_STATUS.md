# PROJECT_STATUS.md - fwber v1.5.1 (DNS Resolution Appendix & Host Mapping)

**Date:** 2026-04-04
**Version:** 1.5.1 "DNS Resolution Appendix & Host Mapping"
**Status:** ✅ **VERIFIED, COMMITTED, AND PROVIDING STRONGER DNS + ROUTING EVIDENCE**

---

## 🎯 What This Release Delivered
This release extended the smoke-check reports again by adding a DNS resolution appendix for the key public hosts.

Delivered:
- DNS appendix in JSON and Markdown smoke reports
- resolved-address capture for frontend, API, geo, and websocket hosts
- stronger cross-checking between hostname resolution and actual HTTP responder fingerprints

## 🚀 Operations Improvements
### Extended `ops/hetzner/scripts/smoke-check.sh`
Smoke reports now also preserve DNS resolution records for:
- frontend host
- API host
- geo host
- websocket host

Each record includes:
- label
- host
- resolver used
- resolved addresses
- notes

### Report output improvements
- `smoke-check-summary.json` now includes `dns_records`
- `smoke-check-summary.md` now includes a `DNS Resolution Appendix`

## 🌐 Real Public Validation Findings (Now Including DNS)
The latest live smoke-check run confirms:
- `fwber.me` resolves to **`216.198.79.1|216.198.79.65`**
- `api.fwber.me` resolves to **`75.119.202.57`**
- `geo.fwber.me` resolves to **`216.198.79.65|64.29.17.1`**
- `ws.fwber.me` resolves to **`69.163.180.228`**

Combined with the endpoint fingerprints, this strengthens the current diagnosis:
- API drift is tied to the Apache-served backend at `75.119.202.57`
- geo drift is tied to host resolution and responder behavior that still points into the wrong hosting topology

## ✅ Validation
- `bash -n ops/hetzner/scripts/smoke-check.sh`
- live smoke-check execution with report artifacts enabled
- JSON `dns_records` inspection
- Markdown `DNS Resolution Appendix` inspection

## ✅ Why This Matters
The smoke-check system now captures:
1. DNS resolution
2. HTTP responder fingerprints
3. remediation diagnostics

That is a significantly better deploy-debug package than raw health checks alone.
