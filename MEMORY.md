# MEMORY.md

## 2026-04-04 — v1.3.9 Premium & Billing Restoration
- The cleanest way to restore premium after the simplification was to rebuild a compact billing slice rather than reactivating every archived economy/referral/token subsystem.
- Mock gateway support is crucial for phased restoration because it keeps billing UX testable locally without live Stripe credentials.
- Premium-gated pages should not rely on the generic API client when `403` means "upgrade required" rather than "session expired"; otherwise premium locks can accidentally trigger logout redirects.
- A minimal `payments` + `subscriptions` ledger is enough to restore status/history/settings pages and webhook reconciliation without bringing back the full token economy.

## 2026-04-04 — v1.3.8 AI Surface Restoration
- The safest first visible restoration after provider wiring was the AI surface because the codebase still contained `AiWingmanService`, frontend hooks, and roast-related share flows.
- Restoring AI routes without restoring every downstream system at once works if we add narrow guards for partially restored dependencies (for example venue-backed date ideas).
- The public roast page is a good restoration wedge because it reactivates a clearly visible feature with relatively low schema complexity compared to marketplace or premium flows.
