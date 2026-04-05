# MEMORY.md

## 2026-04-05 — v1.8.1 Wallet was the next obvious dead-route cluster after Events
- `/wallet` was still referenced by event payment, notification gift routes, and premium filter upsells, while a wallet hook already existed in the frontend.
- Restoring a compact wallet backend/API/page is another strong leverage move because it resolves several visible dead links and reuses existing frontend logic without reintroducing the full archived token economy.

## 2026-04-05 — v1.8.0 Events were another high-value restoration because frontend hooks and test expectations already existed
- The frontend already had event hooks, an EventCard, an invitation list component, and Cypress expectations for `/events`, but the actual routes/backend surface were missing.
- Restoring Events was therefore another strong leverage move like Friends: lots of latent code became useful again with a relatively compact backend reconstruction.
