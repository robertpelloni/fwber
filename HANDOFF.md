# HANDOFF - End of GPT Session

> **Timestamp:** 2026-04-06
> **Version Reached:** 1.7.4
> **Current Model:** GPT
> **Branch:** `restore/pre-simplification-hetzner`

## Executive Summary
This continuation session pushed the rewind branch in two tracks at once:
1. continued restoring approved removed systems as real user-facing surfaces
2. continued converting explicit backend CI failures into source-level compatibility fixes so the branch stays on a Hetzner-safe promotion path

Already pushed during the broader continuation:
- `81f486d93` — `feat: recover rewind navigation and missing activity surfaces (v1.7.0)`
- `6dc1b159c` — `fix: repair rewind avatar test contracts and recommendation caching (v1.7.1)`
- `f576ae411` — `feat: restore rewind boosts gifts referrals and video surfaces (v1.7.2)`
- `d6d7cfa22` — `feat: restore rewind unlock hub and paywall navigation (v1.7.3)`

Completed in this slice:
- investigated the next concrete backend CI failure after `v1.7.2`
- identified the remaining explicit failing seam as `AvatarGenerationTest > service generates prompt with detailed attributes`
- patched avatar prompt generation to query the latest persisted profile row directly instead of trusting a potentially stale cached relation
- recorded release metadata for **v1.7.4**

No processes were manually killed.

---

## What Was Investigated
### Failed backend run inspected
- **Run:** `24015171853`
- **Commit:** `f576ae411` (`v1.7.2`)

### What the log showed
The backend suite was overwhelmingly green.
The remaining explicit failure had narrowed to:
- `Tests\Feature\AvatarGenerationTest > service generates prompt with detailed attributes`

This is strong evidence that the branch is progressing correctly:
- frontend CI has repeatedly gone green
- backend failures are shrinking into isolated behavioral mismatches
- the right strategy remains targeted repair, not broad rollback

---

## Root Cause Analysis
The likely issue was no longer provider selection or cache mocking.

The avatar-generation test creates a `User` first and then creates a `UserProfile` in a separate statement.

That means prompt generation can fail if it relies on:
- a cached `$user->profile` relation
- that was loaded earlier as `null`
- and never refreshed before the prompt is assembled

In that case the generated prompt behaves as though the user has no profile, causing the HTTP assertion callback to fail because expected profile-derived strings are missing.

This kind of stale relation state is a classic rewind-branch compatibility problem:
- not a missing system
- not a deployment blocker directly
- but a subtle test-contract mismatch caused by how older/fuller flows interact with modernized code paths

---

## What Was Changed
### `fwber-backend/app/Services/AvatarGenerationService.php`
Changed the start of `buildPrompt(...)` from:
- relying on `$user->profile`

to:
- querying `$user->profile()->first()` directly

### Why this is correct
This ensures prompt generation reads the latest persisted profile row even when:
- the profile was created after the user model instance already existed
- the relation cache on the model is stale

### Why this is safe for Hetzner/runtime behavior
- it does not change route contracts
- it does not alter provider/network behavior directly
- it simply makes prompt assembly more reliably reflect the actual database state
- that is safe in production and helpful in CI

---

## Validation Context
Earlier in this broader continuation, frontend recovery for additional restored surfaces remained green under production build:
- boosts
- gifts
- referrals
- video
- unlock hub

This slice focused specifically on the next backend CI seam after those restorations.

A fresh backend/frontend GitHub Actions cycle should be triggered after pushing this `v1.7.4` repair.

---

## Files Changed This Slice
### Backend
- `fwber-backend/app/Services/AvatarGenerationService.php`

### Docs / release tracking
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `TODO.md`
- `MEMORY.md`
- `ROADMAP.md`
- `docs/SUBMODULE_DASHBOARD.md`
- `HANDOFF.md`
- `VERSION`
- `VERSION.md`
- `fwber-backend/VERSION`
- `fwber-frontend/VERSION`

---

## Git / Release
### Current tranche target
- **Target Version:** `1.7.4`
- **Recommended Commit Message:** `fix: refresh rewind avatar prompt profile resolution (v1.7.4)`

---

## Best Next Steps
1. Commit and push the `v1.7.4` avatar prompt relation-refresh fix.
2. Re-check the latest restore-branch GitHub Actions runs.
3. If backend CI still remains red, inspect the next explicit failure only and patch it directly.
4. Continue restoring approved removed systems as real destinations while preserving the same constraints:
   - successful frontend production builds
   - no regressions against modern Hetzner/runtime assumptions
   - excluded systems kept out of the primary restored scope emphasis
5. Keep alternating between:
   - surface recovery (so removed systems feel restored)
   - CI/runtime repair (so the branch remains promotable)
