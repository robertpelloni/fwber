# Project Status — fwber v1.0.66 (FWBcoin Rename & Validation Follow-Up)

**Date:** 2026-04-02  
**Version:** 1.0.66 "FWBcoin Rename & Validation Follow-Up"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## FWBcoin Rename & Validation Follow-Up
- **FWBcoin Branding Renamed**: All shipped user-facing legacy token copy in the viral rewards experience and release documentation now reads **FWBcoin**, while the existing backend token accounting contract remains stable.
- **Structured Interest Graph Type Fixes Landed**: The follow-up frontend fixes align the profile API types with the new top-level `interests` field, guard optional interest access in the live profile editor, and unify the match-filter interest option typing.
- **Frontend Validation Closed Cleanly**: A fresh subprocess validation now confirms lint passes with only the long-standing `fwber-frontend/lib/api/photos.ts:476` warning, Next build succeeds, and frontend type-check passes.

## Structured Interest Graph Bridge
- **Profile Interests Now Resolve Into Topics**: Profile updates canonicalize interest values against the existing topic taxonomy, map aliases onto stable topic slugs, preserve unmatched freeform interests, and automatically sync matched topics into `followedTopics()` without disturbing prior follows.
- **Profile API Now Exposes Structured Interest Topics**: Authenticated and public profile responses include `interest_topics` with `match_source` metadata, making it clear which interest hubs come from profile text, explicit follows, or both.
- **Live Profile Editor Uses Topic-Backed Chips**: The `/profile` page now loads topic suggestions from the live topics API, lets users toggle them directly, and merges those selections with the older hobby/music/movie/book/sport buckets when saving.
- **Match Filters Use the Shared Topic Taxonomy**: The shared-interest filter chips in matching now pull from the same topic catalog rather than a duplicated hardcoded list, keeping profile editing and discovery aligned on the same interest graph.
- **Validation Path Is Cleaner on Windows**: `tsconfig.json` now excludes stale renamed dependency folders so `tsc` no longer walks backup `node_modules` directories, and the old stale folder from the earlier repair pass has been removed.

## Current Validation / Delivery State
- **Backend interest-graph coverage is green**: `ProfileUpdateTest` and `SceneDiscoveryFeatureTest` pass with the new canonicalization, topic-sync, and `interest_topics` response assertions.
- **Frontend lint is effectively clean for this slice**: a fresh lint run reports only the long-standing `fwber-frontend/lib/api/photos.ts:476` `react-hooks/exhaustive-deps` warning; the new `/profile` interest-graph work does not add warnings.
- **Frontend build and type-check are now confirmed green in a fresh subprocess**: the earlier `.next` manifest/trace failures were transient artifact races in overlapping Windows worktree builds, not source regressions in the released code.

## ✅ Release Focus
- [x] Rename all shipped legacy token references to FWBcoin.
- [x] Fix the frontend type issues uncovered during the structured interest-graph follow-up validation pass.
- [x] Confirm fresh frontend lint/build/type-check status after the interest-graph follow-up.
- [x] Bridge freeform profile interests into the structured topic graph without replacing the existing topic/follow system.
- [x] Expose structured profile interest topics and source metadata through the profile API.
- [x] Update the live profile and match-filter UI to use topic-backed interest chips.
- [x] Add backend regression coverage for profile-interest canonicalization and scene-summary structured interest exposure.
- [x] Eliminate stale `node_modules` backup folders as a TypeScript validation hazard in the fresh repair worktree.
