# PROJECT_STATUS.md - fwber v1.9.1 (Premium Discovery Filter Restoration)

**Date:** 2026-04-05
**Version:** 1.9.1 "Premium Discovery Filter Restoration"
**Status:** ✅ **ADVANCED MATCH FILTERING NOW MATCHES THE ACTIVE UI AGAIN, WITH SERVER-SIDE TOKEN GATING**

---

## 🎯 What This Release Delivered
This release restored another partially-pruned product seam that was still visible in active UI and profile editing flows:
- premium discovery filter schema
- profile persistence for premium discovery traits
- server-side premium filter enforcement
- full `/matches` advanced filter passthrough
- upgraded discovery filter UX with reset, counts, and missing controls restored

## ✅ Why This Matters
Before this release, the frontend exposed premium discovery concepts like diet, politics, religion, kids, pets, and token-gated filtering, but the active backend schema and controller path were incomplete. That meant users could edit values the backend would not reliably persist and the discovery engine could reference columns that did not exist on drifted databases. This release closes that gap.
