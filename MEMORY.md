# MEMORY.md

## 2026-04-05 — v1.7.2 many "restored" systems still need real top-level destinations or they will continue to feel half-dead
- The rewind branch already contained boosts, gifts, referrals, and video functionality, but these systems still relied too heavily on scattered modals or inline triggers.
- Adding real pages for them is high leverage because it turns hidden implementation into visible restored product surface without destabilizing Hetzner deployment contracts.
- The restore strategy should keep pairing backend/contract recovery with page-level destination recovery so features are not only present, but actually reachable.

## 2026-04-05 — v1.7.1 some rewind-branch test failures are caused by testing shortcuts that are harmless in lean branches but wrong for full-surface suites
- The avatar-generation code was still using testing-only early returns when provider credentials were absent, which prevented `Http::assertSent()`-style rewind tests from ever observing outbound generation calls.
- Recommendation responses on the richer branch also needed their older tagged-cache contract restored because the CI suite asserts personalized recommendation caching explicitly.
- The right pattern on the rewind branch is to preserve observable contracts under `Http::fake()` / `Cache::shouldReceive()` rather than prematurely short-circuiting inside test environments.

## 2026-04-05 — v1.7.0 A restore branch can still feel broken even when the code is present if the shell highlights the wrong surfaces
- The rewind branch contained many restored systems, but the main app shell still pointed users toward excluded federation/journal-era branches and still lacked real `/activity` and `/notifications` destinations.
- Restoring breadth is not enough; the signed-in shell must also spotlight the approved surfaces so the product actually feels restored.
- The next rewind passes should keep checking for this pattern: code may exist, but if navigation and dashboard entry points are wrong, the branch will still appear half-restored.

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
