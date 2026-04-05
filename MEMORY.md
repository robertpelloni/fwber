# MEMORY.md

## 2026-04-05 — messaging and federation on the rewind branch needed direct compatibility repair, not more replay first
- After profile persistence and frontend build fixes, the next meaningful failures were in direct messaging and WebFinger/federation behavior.
- The successful fixes were branch-local: allowing numeric or string `receiver_id`, making message event append non-blocking, and restoring federated-only WebFinger behavior with the expected federation actor URI.
- This is another confirmation that the rewind branch is now in a mixed mode where replay gets us close, but local compatibility patches finish the job.

## 2026-04-05 — direct branch-specific fixes are now an expected part of the rewind strategy
- Replay alone is not enough. Once the restore branch absorbed enough modern infra/runtime work, real compatibility bugs surfaced that only exist in the older full-feature branch.
- The successful fixes in recent tranches were not just replay commits but branch-local repairs: non-blocking event-store/event-bus append handling, missing frontend UI primitives, and restored messaging/WebFinger contracts.
- This confirms the rewind strategy is now in the mixed phase: replay modern fixes where possible, then patch restore-specific collisions directly.

## 2026-04-05 — frontend rewind branch needed missing primitives, not just workflow modernization
- Once the workflow modernizations landed, the richer frontend branch still failed to compile because old pages referenced UI primitives (`avatar`, `progress`, `select`) that were absent in the branch state.
- Adding lightweight compatible components was a high-leverage way to get the full-feature frontend building locally again without waiting for a larger UI-library replay.
