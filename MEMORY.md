# MEMORY.md

## 2026-04-05 — replaying workflow modernization onto the rewind branch was immediately justified by a real CI failure
- The first restore-branch frontend workflow run failed because the older branch still carried stale workflow assumptions and no suitable lockfile path resolution.
- After that failure, replaying `847f43f26` and `18f3539e9` onto the rewind branch was the right next move.
- This confirms the replay order matters: CI/deploy modernization needs to happen early, not after deeper app-layer merges.

## 2026-04-05 — the rewind branch can already accept modern Hetzner commits with manageable conflicts
- `restore/pre-simplification-hetzner` successfully absorbed:
  - `11250c5ec` (Hetzner deployment docs)
  - `59f132e38` (Hetzner ops templates + frontend env alignment)
  - `847f43f26` (GitHub backend deploy switched to Hetzner)
  - `18f3539e9` (workflow stabilization after cutover)
- Early replay conflicts were mostly documentation/version files and missing workflow files that needed to be kept from the replayed commits.
- That is still a very good sign: replay is viable and conflict profile remains manageable.

## 2026-04-05 — v1.9.2 created the actual rewind track, not just the idea
- The dedicated restore branch is now `restore/pre-simplification-hetzner`.
- It is rooted at `a636a53c3`, the final pre-simplification snapshot before `2a3f8aa40`.
- The repo-level diff from `a636a53c3..HEAD` is enormous: 827 files changed, 20,665 insertions, 56,068 deletions.
- That confirms rewind + replay is the correct strategy for “bring back everything” rather than continuing only archive-by-archive restoration forever.
