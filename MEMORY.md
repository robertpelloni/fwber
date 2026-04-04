# MEMORY.md

## 2026-04-04 — v1.3.7 Restoration Foundation
- Broad restoration after the simplification should be sequenced. Re-adding provider/container wiring first is safer than restoring route/controller surfaces into an unresolved dependency graph.
- `AiServiceProvider` and `PaymentServiceProvider` were previously archived even though many AI/payment-related services and classes still exist in the codebase.
- The next safest restoration slice is AI/Wingman/roast because the frontend still contains hooks and some UI remnants for it, while payment/merchant restoration will likely require more schema and route work.

## 2026-04-04 — v1.3.6 Migration Column-Guard Hardening
- Making the index optimization migration idempotent for duplicate indexes was necessary but not sufficient; deploy targets can also drift structurally and miss one or more indexed columns.
- Performance/index migrations should be defensive in two dimensions:
  1. skip indexes that already exist
  2. skip indexes whose referenced columns are absent
- The production failure on `idx_photos_user_order` indicates at least one deploy target has a `photos` table shape different from the current local migration set.
- These kinds of performance-only migrations should degrade safely rather than block the whole deployment.
