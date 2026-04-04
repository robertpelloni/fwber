# MEMORY.md

## 2026-04-04 — v1.6.3 Remaining GitHub failures were mostly workflow drift, not product regressions
- After the Hetzner deploy pipeline went green, the remaining red GitHub runs were mostly caused by duplicate or stale workflows.
- `backend-tests.yml` needed SQLite env during migration setup, `frontend-build.yml` needed the correct lockfile path for npm caching, and the older monolithic `ci.yml` / `deploy.yml` needed to stop duplicating modern dedicated workflows.

## 2026-04-04 — v1.6.2 GitHub Hetzner deploy is now genuinely validated
- After adding the Hetzner repository secrets and fixing rustup PATH loading, the GitHub `Deploy Backend (Hetzner)` workflow completed successfully end-to-end.
- The key nuance was that the first post-fix run still used the old server-side script content because the remote script is loaded before its own internal `git pull`; the next run used the updated script and succeeded.
