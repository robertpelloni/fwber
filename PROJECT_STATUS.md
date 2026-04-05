# PROJECT_STATUS.md - fwber v1.9.8 (Restore Branch Messaging + WebFinger Stabilization)

**Date:** 2026-04-05
**Version:** 1.9.8 "Restore Branch Messaging + WebFinger Stabilization"
**Status:** ✅ **THE REWIND BRANCH NOW HAS DIRECT FIXES FOR MESSAGING AND FEDERATED WEBFINGER CONTRACTS, WITH THOSE TARGETED BACKEND TESTS PASSING LOCALLY**

---

## 🎯 What This Release Delivered
This release extended the direct compatibility-fix phase on the restore branch:
- direct messaging no longer fails closed when restored event-bus publishing drifts
- WebFinger again honors federated-only users and federation actor URI expectations
- message request validation now supports both local numeric receivers and federated string receivers
- targeted restore-branch messaging + federation tests now pass locally

## ✅ Why This Matters
The rewind branch is steadily moving from surface compatibility into real feature-contract recovery. Messaging and WebFinger are core cross-cutting systems that many richer pre-simplification surfaces depend on, so stabilizing them is a meaningful step toward a usable full-feature branch on modern Hetzner infrastructure.
