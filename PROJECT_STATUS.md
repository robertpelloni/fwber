# PROJECT_STATUS.md - fwber v1.8.13 (Surface Polish & Runtime Hardening)

**Date:** 2026-04-07
**Version:** 1.8.13 "Surface Polish & Runtime Hardening"
**Status:** ✅ **MAINLINE IS STABLE, HUB SURFACES ARE POLISHED, AND RUNTIME IS HARDENED**

---

## 🎯 What This Release Delivered
This release focused on small but meaningful surface and runtime repairs following the mainline promotion.

Delivered:
- hardened `VectorService` against missing RediSearch (prevents queue job failures)
- surfaced missing leaf routes: `/rate-my-pussy`, `/groups/matching`, `/gifts/history`
- removed excluded Federation/Journal links from Settings
- verified `v1.8.12` green streak continues

## ✅ Why This Matters
Now that the branch has been promoted to `main`, every small polish pass improves the actual live experience. Hardening the vector service ensures that background jobs don't fail silently on standard Redis instances, while surfacing the remaining leaf routes ensures the full breadth of the restored branch is accessible through the new hub-based product map.
