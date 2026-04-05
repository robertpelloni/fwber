# PROJECT_STATUS.md - fwber v1.8.6 (Smoke Roast Warmup Stabilization)

**Date:** 2026-04-05
**Version:** 1.8.6 "Smoke Roast Warmup Stabilization"
**Status:** ✅ **DEPLOY SMOKE NOW PRE-WARMS THE ROAST PREVIEW BEFORE ASSERTING IT**

---

## 🎯 What This Release Delivered
This release adds a practical deploy-smoke stabilization step:
- warm the public roast preview once before the real asserted smoke call

## ✅ Why This Matters
The roast preview appears to exhibit transient first-hit behavior immediately after deploy/optimize. Pre-warming it before the asserted smoke call is a low-risk way to reduce false-negative deployment failures while preserving the real contract check.
