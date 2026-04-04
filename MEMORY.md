# MEMORY.md

## 2026-04-04 — v1.3.6 Migration Column-Guard Hardening
- Making the index optimization migration idempotent for duplicate indexes was necessary but not sufficient; deploy targets can also drift structurally and miss one or more indexed columns.
- Performance/index migrations should be defensive in two dimensions:
  1. skip indexes that already exist
  2. skip indexes whose referenced columns are absent
- The production failure on `idx_photos_user_order` indicates at least one deploy target has a `photos` table shape different from the current local migration set.
- These kinds of performance-only migrations should degrade safely rather than block the whole deployment.

## 2026-04-04 — v1.3.5 Deployment Migration Idempotency
- The `optimize_core_indexes` migration was not deployment-safe because partial application on a real MySQL target left indexes behind, causing duplicate-key failures on retry.
- Performance/index migrations should be treated as idempotent infrastructure steps, not fragile one-shot changes, especially in environments where deployments can be interrupted.
- Checking index existence inside the migration itself is a pragmatic fix because it protects future retries without needing manual DBA cleanup first.
- The production login endpoint did not reproduce as a simple invalid-credentials failure during direct curl checks; both `https://www.fwber.me/api/auth/login` and `https://api.fwber.me/api/auth/login` returned proper 422 JSON for invalid credentials. That suggests the reported 500 is likely environment-specific or triggered by a different request path/input condition.
- Added `browser-storage.ts` and switched analytics session persistence to safe storage wrappers so restricted contexts can fail quietly instead of throwing storage-access exceptions during startup.
