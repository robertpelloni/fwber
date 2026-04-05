# MEMORY.md

## 2026-04-04 — v1.6.4 Frontend workflow failure was real lockfile drift
- After workflow cleanup, the remaining frontend GitHub failure was not a workflow-definition problem but a real `package-lock.json` sync issue.
- Running `npm install` in `fwber-frontend/` regenerated the lockfile, and a clean `npm ci && npm run build` then succeeded locally.

## 2026-04-04 — v1.6.3 Remaining GitHub failures were mostly workflow drift, not product regressions
- After the Hetzner deploy pipeline went green, the remaining red GitHub runs were mostly caused by duplicate or stale workflows.
- `backend-tests.yml` needed SQLite env during migration setup, `frontend-build.yml` needed the correct lockfile path for npm caching, and the older monolithic `ci.yml` / `deploy.yml` needed to stop duplicating modern dedicated workflows.
