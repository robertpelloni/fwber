# MEMORY.md

## 2026-04-05 — after route-case fixes, the next restore-branch failures moved deeper into profile/onboarding semantics
- The latest backend CI log on the restore branch no longer centered on missing `Api` controller classes.
- It now highlights deeper behavioral mismatches such as profile update persistence, onboarding/profile payload expectations, travel-mode persistence, and root-route/webfinger/nodeinfo contract differences.
- This is useful progress: it means the rewind branch is moving past surface-level Linux/class-resolution failures into real contract reconciliation.

## 2026-04-05 — the restore branch is now deep enough that Linux-specific app breakage is surfacing, not just workflow drift
- After replaying the health/smoke/deploy stack, backend CI on the restore branch surfaced a real Linux route-resolution issue: routes referenced `App\Http\Controllers\API\...` while the actual namespace/path is `Api\...`.
- That mismatch is easy to miss on Windows but breaks on Linux CI. A direct restore-branch fix was warranted and applied as `d4d073e4f`.
- Expect more of these reconciliation issues as the richer old branch meets the modern Linux/Hetzner environment.

## 2026-04-05 — replay order continues to matter
- Workflow modernization had to come before deeper app-layer debugging.
- Smoke/deploy hardening had to land before route/runtime verification work.
- Now that those are in, the remaining failures are increasingly useful because they reveal actual feature-contract mismatches instead of infra noise.
