# MEMORY.md

## 2026-04-05 — the restore branch is now deep enough that Linux-specific app breakage is surfacing, not just workflow drift
- After replaying the health/smoke/deploy stack, backend CI on the restore branch surfaced a real Linux route-resolution issue: routes referenced `App\Http\Controllers\API\...` while the actual namespace/path is `Api\...`.
- That mismatch is easy to miss on Windows but breaks on Linux CI. A direct restore-branch fix was warranted and applied as `d4d073e4f`.
- Expect more of these reconciliation issues as the richer old branch meets the modern Linux/Hetzner environment.

## 2026-04-05 — replaying workflow modernization onto the rewind branch was immediately justified by a real CI failure
- The first restore-branch frontend workflow run failed because the older branch still carried stale workflow assumptions and no suitable lockfile path resolution.
- After that failure, replaying `847f43f26`, `18f3539e9`, `81781ffb1`, `6f1251b18`, and `e0fee531a` onto the rewind branch was the right next move.
- This confirms the replay order matters: CI/deploy modernization needs to happen early, not after deeper app-layer merges.

## 2026-04-05 — the rewind branch can already accept modern Hetzner commits with manageable conflicts
- `restore/pre-simplification-hetzner` has now successfully absorbed docs, ops templates, workflow modernization, health endpoints, smoke tooling, report tooling, ACL/logging changes, and nginx/deploy hardening.
- Early replay conflicts were mostly documentation/version files and deploy script merges, which remain manageable.
- That is still a strong signal that rewind + replay is viable.
