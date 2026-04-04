# PROJECT_STATUS.md - fwber v1.6.0 (GitHub Backend Deploy Switched to Hetzner)

**Date:** 2026-04-04
**Version:** 1.6.0 "GitHub Backend Deploy Switched to Hetzner"
**Status:** ✅ **BACKEND IS LIVE ON HETZNER AND CI DEPLOY TARGET NOW MATCHES REAL INFRASTRUCTURE**

---

## 🎯 What This Release Delivered
This release fixes deployment automation drift.

Delivered:
- confirmed the live backend is deploying correctly on Hetzner via the in-repo deploy script
- replaced the stale GitHub Actions backend deploy workflow that still pointed at DreamHost
- aligned GitHub deployment automation with the actual Hetzner production topology

## ✅ Current Reality
- Manual/SSH-driven Hetzner deploys are working.
- The stale GitHub Action was still configured for DreamHost before this release.
- The workflow now targets Hetzner instead.
