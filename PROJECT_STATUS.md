# PROJECT_STATUS.md - fwber v1.6.9 (Frontend Workflow Install Strategy Fix)

**Date:** 2026-04-05
**Version:** 1.6.9 "Frontend Workflow Install Strategy Fix"
**Status:** ✅ **FRONTEND CI ISOLATED TO INSTALL-STRATEGY DRIFT; PRAGMATIC FIX APPLIED**

---

## 🎯 What This Release Delivered
This release applies a pragmatic CI + operations stabilization pass after the frontend workflow and post-deploy verification layer both showed environment-sensitive drift.

Delivered:
- switched the frontend GitHub workflow from `npm ci` to `npm install --no-fund --no-audit`
- hardened the Hetzner smoke-check script so it auto-normalizes the API base URL and can discover the Reverb app key from the backend config
- hardened the Hetzner deploy script so it re-applies tracked nginx configs for api/ws/geo before nginx validation + reload
- confirmed a clean live smoke result on Hetzner, including websocket upgrade verification
- confirmed the repaired frontend automation path too: the latest frontend GitHub workflow pass is green and the live Vercel bundles point at the recovered API/realtime hosts

## ✅ Why This Matters
The frontend dependency graph still includes wallet/native-adjacent packages whose optional/platform-sensitive resolution can diverge between local and GitHub Linux environments. At the same time, the production verifier itself needed to be more resistant to operator/env drift.

This release improves both:
- CI signal becomes more resilient
- deploy-time verification becomes closer to a real source-of-truth contract check
