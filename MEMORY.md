# MEMORY.md

## 2026-04-04 — v1.3.8 AI Surface Restoration
- The safest first visible restoration after provider wiring was the AI surface because the codebase still contained `AiWingmanService`, frontend hooks, and roast-related share flows.
- Restoring AI routes without restoring every downstream system at once works if we add narrow guards for partially restored dependencies (for example venue-backed date ideas).
- The public roast page is a good restoration wedge because it reactivates a clearly visible feature with relatively low schema complexity compared to marketplace or premium flows.

## 2026-04-04 — v1.3.7 Restoration Foundation
- Broad restoration after the simplification should be sequenced. Re-adding provider/container wiring first is safer than restoring route/controller surfaces into an unresolved dependency graph.
- `AiServiceProvider` and `PaymentServiceProvider` were previously archived even though many AI/payment-related services and classes still exist in the codebase.
- The next safest restoration slice is AI/Wingman/roast because the frontend still contains hooks and some UI remnants for it, while payment/merchant restoration will likely require more schema and route work.
