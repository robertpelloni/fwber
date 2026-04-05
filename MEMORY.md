# MEMORY.md

## 2026-04-05 — user direction has now shifted toward a pre-simplification rewind merged with Hetzner hardening
- The newest instruction is no longer just incremental restoration. The user wants a rewind-style recovery: bring back the removed systems as they existed before the Great Simplification, while preserving the current Hetzner deployment reality.
- The safest baseline candidate appears to be the final pre-simplification snapshot right before `refactor: execute 'The Great Simplification' removing all non-core features (v1.2.0)`.
- This should likely be handled as a restoration branch plus selective replay/merge of post-simplification Hetzner, deployment, smoke, CI, and production-stability commits.

## 2026-04-05 — v1.9.1 premium discovery restoration fixed a real schema/controller drift problem, not just UI polish
- Active frontend profile editing and discovery filtering still referenced diet, politics, religion, pets, and kids signals.
- The simplified active schema had dropped some of those columns, while `AIMatchingService` still referenced them.
- Restoring the columns and controller persistence was necessary to make the current live UI truthful again and to prevent real schema-drift breakage.

## 2026-04-05 — v1.9.0 token-gated unlocks were another strong restoration because both backend hints and frontend expectations already existed
- The repo already had `ContentUnlockGate`, `PhotoUnlock`, `unlock_price` typing on photo payloads, and locked match-insights Cypress expectations.
- The real missing pieces were the controller/routes/schema plus frontend state handling for locked/unlocked insight responses.
- Restoring the unlock ledger as a compact shared backend service is a good pattern for remaining token-era spend surfaces.
