# MEMORY.md

## 2026-04-05 — v1.9.2 created the actual rewind track, not just the idea
- The dedicated restore branch is now `restore/pre-simplification-hetzner`.
- It is rooted at `a636a53c3`, the final pre-simplification snapshot before `2a3f8aa40`.
- The repo-level diff from `a636a53c3..HEAD` is enormous: 827 files changed, 20,665 insertions, 56,068 deletions.
- That confirms rewind + replay is the correct strategy for “bring back everything” rather than continuing only archive-by-archive restoration forever.

## 2026-04-05 — user direction has now shifted toward a pre-simplification rewind merged with Hetzner hardening
- The newest instruction is no longer just incremental restoration. The user wants a rewind-style recovery: bring back the removed systems as they existed before the Great Simplification, while preserving the current Hetzner deployment reality.
- The safest baseline candidate appears to be the final pre-simplification snapshot right before `refactor: execute 'The Great Simplification' removing all non-core features (v1.2.0)`.
- This should likely be handled as a restoration branch plus selective replay/merge of post-simplification Hetzner, deployment, smoke, CI, and production-stability commits.

## 2026-04-05 — v1.9.1 premium discovery restoration fixed a real schema/controller drift problem, not just UI polish
- Active frontend profile editing and discovery filtering still referenced diet, politics, religion, pets, and kids signals.
- The simplified active schema had dropped some of those columns, while `AIMatchingService` still referenced them.
- Restoring the columns and controller persistence was necessary to make the current live UI truthful again and to prevent real schema-drift breakage.
