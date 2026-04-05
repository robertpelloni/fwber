# MEMORY.md

## 2026-04-05 — direct branch-specific fixes are now an expected part of the rewind strategy
- Replay alone is not enough. Once the restore branch absorbed enough modern infra/runtime work, real compatibility bugs surfaced that only exist in the older full-feature branch.
- The successful fixes in this tranche were not replay commits but branch-local repairs: non-blocking event-store/event-bus append handling and missing frontend UI primitives.
- This confirms the rewind strategy is now in the mixed phase: replay modern fixes where possible, then patch restore-specific collisions directly.

## 2026-04-05 — after route-case fixes, the next restore-branch failures moved deeper into profile/onboarding semantics
- The latest backend CI log no longer centered on missing `Api` controller classes.
- It exposed deeper behavioral mismatches around profile updates, onboarding/profile payload expectations, travel-mode persistence, and other semantics.
- Wrapping event append failures in `ProfileController` and `MatchController` immediately converted several backend failures into passing tests.

## 2026-04-05 — frontend rewind branch needed missing primitives, not just workflow modernization
- Once the workflow modernizations landed, the richer frontend branch still failed to compile because old pages referenced UI primitives (`avatar`, `progress`, `select`) that were absent in the branch state.
- Adding lightweight compatible components was a high-leverage way to get the full-feature frontend building locally again without waiting for a larger UI-library replay.
