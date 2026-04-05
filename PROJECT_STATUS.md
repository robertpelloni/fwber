# PROJECT_STATUS.md - fwber v1.6.9 (Restore Branch CI Compatibility Sweep)

**Date:** 2026-04-05
**Version:** 1.6.9 "Restore Branch CI Compatibility Sweep"
**Status:** ✅ **RESTORE BRANCH LOCAL SUITE GREEN; CI ROOT CAUSES PATCHED; BROAD REWIND STILL IN RECONCILIATION**

---

## 🎯 What This Release Delivered
This release stopped treating the restore branch as a vague "it still fails somewhere" problem and instead repaired the concrete compatibility faults that were keeping the broader pre-simplification surface from behaving like the earlier snapshot.

Delivered:
- `AvatarGenerationService` now honors restore-branch test config overrides and emits the richer prompt language expected by the old AI-avatar surface.
- `TaggedCache` now calls `Cache::tags(...)` first so mocked tagged-cache controller tests keep working while fallback invalidation still protects non-taggable runtime stores.
- restore-branch frontend Sentry integration now uses modern App Router instrumentation instead of the stale placeholder/deprecated client config shape.
- restore-branch frontend E2E crypto no longer hard-imports a missing generated WASM module, so the broader restored UI can build in CI/worktrees without artifact-specific failures.
- restore-branch Next.js build no longer emits the stale `disableLogger` deprecation warning.

## ✅ Why This Matters
The repo is still in a compatibility phase because the broader rewind branch contains old feature breadth plus new deployment/runtime assumptions. This release directly narrows that gap:
- local backend validation succeeded with **425 passing tests**
- local frontend production build succeeded
- the specific GitHub CI failures previously observed were mapped to real restore-branch drift and patched at the source
