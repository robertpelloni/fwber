# MEMORY.md

## 2026-04-04 — v1.3.5 Deployment Migration Idempotency
- The `optimize_core_indexes` migration was not deployment-safe because partial application on a real MySQL target left indexes behind, causing duplicate-key failures on retry.
- Performance/index migrations should be treated as idempotent infrastructure steps, not fragile one-shot changes, especially in environments where deployments can be interrupted.
- Checking index existence inside the migration itself is a pragmatic fix because it protects future retries without needing manual DBA cleanup first.
- The production login endpoint did not reproduce as a simple invalid-credentials failure during direct curl checks; both `https://www.fwber.me/api/auth/login` and `https://api.fwber.me/api/auth/login` returned proper 422 JSON for invalid credentials. That suggests the reported 500 is likely environment-specific or triggered by a different request path/input condition.
- Added `browser-storage.ts` and switched analytics session persistence to safe storage wrappers so restricted browser contexts degrade quietly instead of throwing storage-access exceptions during startup.

## 2026-04-04 — v1.3.4 Console Error Sweep
- Real browser-console reports uncovered several hidden contract problems that local green builds would not surface on their own.
- The homepage still contained links to archived routes (`/roast`, `/rate-my-pussy`), which caused Next prefetch 404 noise on production.
- The frontend analytics client was actively posting to `/api/analytics/events`, but the simplified backend route set no longer exposed that endpoint even though `AnalyticsController::store()` still existed.
- Notification preferences had another frontend/backend route mismatch: the frontend already used `/notification-preferences/{type}`, while the active backend route still pointed at a path without `{type}`.
- Auth flows should never assume JSON on error. If production emits HTML, warnings, or malformed content during a 500, the frontend must degrade into a readable error instead of throwing a parser exception.
