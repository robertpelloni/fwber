# MEMORY.md

## 2026-04-04 — v1.6.5 Hetzner backend drift was app/schema inconsistency, not dead infrastructure
- Direct Hetzner inspection showed the fwber backend stack itself was alive: nginx, php-fpm, queue worker, Reverb, geo service, Redis, and MySQL were all up.
- The live failures were caused by application drift instead: the root route rendered a missing `welcome` view, `php artisan route:list` broke on a missing `WebFingerController`, and dashboard activity crashed because the `user_matches` table was missing even though migrations claimed it had already run.
- `DashboardController` also hid a real PHP 8.4 type bug where the `limit` query string remained a string and later crashed `array_slice()`.
- A corrective migration plus controller hardening is the right repair shape when the live DB can be recreated and schema drift has already escaped normal migration guarantees.
- Monolog file ownership drift is also a real Hetzner operational footgun: log rotation can hand files back to the web runtime user and later break deploy-user artisan commands unless permissions are normalized.

## 2026-04-04 — v1.6.4 Frontend workflow failure was real lockfile drift
- After workflow cleanup, the remaining frontend GitHub failure was not a workflow-definition problem but a real `package-lock.json` sync issue.
- Running `npm install` in `fwber-frontend/` regenerated the lockfile, and a clean `npm ci && npm run build` then succeeded locally.

## 2026-04-04 — v1.6.3 Remaining GitHub failures were mostly workflow drift, not product regressions
- After the Hetzner deploy pipeline went green, the remaining red GitHub runs were mostly caused by duplicate or stale workflows.
- `backend-tests.yml` needed SQLite env during migration setup, `frontend-build.yml` needed the correct lockfile path for npm caching, and the older monolithic `ci.yml` / `deploy.yml` needed to stop duplicating modern dedicated workflows.
