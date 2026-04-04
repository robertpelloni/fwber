# PROJECT_STATUS.md - fwber v1.6.3 (Workflow Stabilization Sweep)

**Date:** 2026-04-04
**Version:** 1.6.3 "Workflow Stabilization Sweep"
**Status:** ✅ **PRIMARY DEPLOY PIPELINE GREEN; DUPLICATE CI NOISE BEING ELIMINATED**

---

## 🎯 What This Release Delivered
This release focuses on stabilizing the remaining GitHub automation noise after backend Hetzner deployment was proven green.

Delivered:
- backend CI now uses SQLite correctly during setup
- frontend build workflow now points npm cache at the real frontend lockfile
- duplicate monolithic CI jobs removed from `ci.yml`
- legacy `deploy.yml` is now manual-only container publishing instead of an always-on failing pipeline

## ✅ Why This Matters
The repo had reached the point where the real Hetzner backend deployment workflow was green, but older duplicate workflows were still failing and obscuring the signal. This release reduces that noise and aligns automation with the real stack.
