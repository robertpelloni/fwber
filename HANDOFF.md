# HANDOFF - End of GPT Session

> **Timestamp:** 2026-04-05
> **Version Reached:** 1.6.9
> **Current Model:** GPT

## Executive Summary
This session continued the production-hardening loop after confirming that:
- Hetzner backend deploys are green again
- backend CI is green again
- repository hygiene is green again
- but the dedicated frontend GitHub build was still failing even after lockfile resync and Node 24 alignment

That remaining frontend failure turned out to be a CI install-strategy problem caused by platform-sensitive optional dependencies.

This session completed **v1.6.9 "Frontend Workflow Install Strategy Fix"**.

---

## What Was Root-Caused
### 1. Backend side kept improving
By this point:
- `api.fwber.me/` was confirmed healthy and returning JSON
- WebFinger no longer 500ed
- Hetzner deploy workflow was green again after ACL-based log repair

### 2. Frontend GitHub build still failed
Even after:
- lockfile resync
- upgrading the workflow to Node 24

GitHub still failed during frontend dependency installation.

Failure signature:
- `npm ci` rejected the install because of platform-sensitive optional dependency drift
- errors referenced packages like:
  - `bufferutil`
  - `utf-8-validate`
  - nested `react-native`
  - nested `react@19.2.4`

This points at wallet/native-adjacent dependency branches behaving differently between environments, even when the actual local build succeeds.

### 3. Conclusion
The issue was no longer about source app correctness.
It was about using a too-strict install mode for a dependency graph that still contains optional/platform-variant branches.

---

## What Was Changed
Updated:
- `.github/workflows/frontend-build.yml`

Change:
- replaced `npm ci` with:
  - `npm install --no-fund --no-audit`

Why:
- this restores build verification signal while the dependency graph is still being simplified
- the workflow still performs the real production build, which is the important verification target right now

---

## Validation Context
### Already green
- `Deploy Backend (Hetzner)` ✅
- `Backend CI (Tests & Linting)` ✅
- `Repository Hygiene` ✅

### Remaining next validation target
- rerun `Frontend Build & Deploy (Vercel)` after this install-strategy fix lands

---

## Files Changed in This Slice
- `.github/workflows/frontend-build.yml`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `TODO.md`
- `MEMORY.md`
- `ROADMAP.md`
- `DEPLOY.md`
- `HANDOFF.md`
- version files

---

## Git / Release
- **Target Version:** `1.6.9`
- **Recommended Commit Message:** `fix: use npm install in frontend github workflow for optional dependency drift (v1.6.9)`

---

## Best Next Steps
1. Commit and push `v1.6.9`
2. Re-run `Frontend Build & Deploy (Vercel)`
3. Re-check live `/nodeinfo/2.0` after the current backend deploy path has the guarded controller in place
4. Continue live frontend runtime verification:
   - dashboard API behavior
   - E2E restore behavior
   - realtime connected badge
5. Only then begin broader full-feature restoration planning

No processes were manually killed.
