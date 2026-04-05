# MEMORY.md

## 2026-04-05 — v1.6.9 Restore-branch failures were mostly compatibility drift, not fresh feature bugs
- The rewind branch was closer to working breadth than it initially looked; the red CI signal was concentrated around a few old-assumption seams:
  - avatar generation prompt/config behavior no longer matched its tests
  - tagged-cache helper no longer invoked `Cache::tags()` under mocked expectations
  - frontend Sentry setup had fallen behind modern App Router conventions
  - frontend E2E crypto still assumed generated WASM bindings existed in every checkout
- Local restore-worktree validation after the fixes reached **425 passing backend tests** and a green production frontend build.
- The right next move is aggressive rewind reconciliation, not endless tiny selective restores.

## 2026-04-05 — v1.6.8 Discovery routes still need schema guards even when federation is de-scoped
- `/nodeinfo/2.0` was still 500ing live because `NodeInfoController` assumed `user_profiles.is_federated` existed.
- Even when federation is not the active product focus, discovery routes must degrade safely instead of crashing on absent optional schema.
- Frontend CI also needed runtime-family alignment: the lockfile/build was validated under Node 24 locally, so GitHub should match that instead of staying on Node 20.

## 2026-04-05 — v1.6.6 Daily-log shared write should use ACLs, not Monolog chmod from deploy user
- The first live deploy after the backend stability patch failed because Monolog's `permission` option tried to chmod a daily log file owned by `www-data` from a deploy-user artisan process.
- The correct repair shape is shared ACLs on `storage/logs` plus removing the Monolog permission override, not repeated per-file chmod attempts in deploy code.
