# PROJECT_STATUS.md - fwber v1.6.9 (Frontend Workflow Install Strategy Fix)

**Date:** 2026-04-05
**Version:** 1.6.9 "Frontend Workflow Install Strategy Fix"
**Status:** ✅ **FRONTEND CI ISOLATED TO INSTALL-STRATEGY DRIFT; PRAGMATIC FIX APPLIED**

---

## 🎯 What This Release Delivered
This release applies a pragmatic CI fix after the frontend workflow remained blocked by platform-sensitive dependency resolution.

Delivered:
- switched the frontend GitHub workflow from `npm ci` to `npm install --no-fund --no-audit`
- kept the build validation step intact

## ✅ Why This Matters
The frontend dependency graph still includes wallet/native-adjacent packages whose optional/platform-sensitive resolution can diverge between local and GitHub Linux environments. This change restores CI signal while deeper dependency cleanup remains pending.
