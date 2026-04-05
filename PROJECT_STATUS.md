# PROJECT_STATUS.md - fwber v1.9.7 (Restore Branch Profile + Frontend Build Stabilization)

**Date:** 2026-04-05
**Version:** 1.9.7 "Restore Branch Profile + Frontend Build Stabilization"
**Status:** ✅ **THE REWIND BRANCH NOW HAS DIRECT FIXES FOR PROFILE PERSISTENCE, MATCH ACTION EVENT-BUS DRIFT, AND SEVERAL FRONTEND BUILD BREAKERS**

---

## 🎯 What This Release Delivered
This release moved the restore branch from broad infra replay into concrete branch-specific compatibility repair:
- profile update flow no longer fails closed when event-store append drifts
- match action flow no longer fails closed when legacy Redis event-bus publishing drifts
- missing UI primitives were restored for the richer frontend branch
- broken council / merchant vibe / WASM benchmark frontend sources were cleaned up
- targeted restore-branch backend tests and a local frontend production build both succeeded

## ✅ Why This Matters
The rewind branch is now proving it can absorb not only replayed Hetzner/runtime commits but also direct compatibility fixes specific to the older full-feature codebase. That is the expected shape of the remaining work: replay what can be replayed, then patch the branch where old assumptions collide with the modern environment.
