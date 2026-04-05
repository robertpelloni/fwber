# MEMORY.md

## 2026-04-05 — the rewind branch can already accept modern Hetzner commits with manageable conflicts
- `restore/pre-simplification-hetzner` successfully absorbed:
  - `11250c5ec` (Hetzner deployment docs)
  - `59f132e38` (Hetzner ops templates + frontend env alignment)
- The first replay conflicts were mostly documentation/version and one workflow file that existed in the replayed commit but not in the pre-simplification baseline.
- That is a very good sign: replay is viable, and the early conflict profile is manageable.

## 2026-04-05 — v1.9.2 created the actual rewind track, not just the idea
- The dedicated restore branch is now `restore/pre-simplification-hetzner`.
- It is rooted at `a636a53c3`, the final pre-simplification snapshot before `2a3f8aa40`.
- The repo-level diff from `a636a53c3..HEAD` is enormous: 827 files changed, 20,665 insertions, 56,068 deletions.
- That confirms rewind + replay is the correct strategy for “bring back everything” rather than continuing only archive-by-archive restoration forever.

## 2026-04-05 — user direction has now shifted toward a pre-simplification rewind merged with Hetzner hardening
- The newest instruction is no longer just incremental restoration. The user wants a rewind-style recovery: bring back the removed systems as they existed before the Great Simplification, while preserving the current Hetzner deployment reality.
- The safest baseline candidate appears to be the final pre-simplification snapshot right before `refactor: execute 'The Great Simplification' removing all non-core features (v1.2.0)`.
- This should likely be handled as a restoration branch plus selective replay/merge of post-simplification Hetzner, deployment, smoke, CI, and production-stability commits.
