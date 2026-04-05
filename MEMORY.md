# MEMORY.md

## 2026-04-05 — v1.8.0 Events were another high-value restoration because frontend hooks and test expectations already existed
- The frontend already had event hooks, an EventCard, an invitation list component, and Cypress expectations for `/events`, but the actual routes/backend surface were missing.
- Restoring Events was therefore another strong leverage move like Friends: lots of latent code became useful again with a relatively compact backend reconstruction.

## 2026-04-05 — v1.7.2 Live Hetzner repo ownership drift can break an otherwise healthy deploy pipeline
- A push-triggered Hetzner deploy failed with `insufficient permission for adding an object to repository database .git/objects` even though the workflow and app code were otherwise healthy.
- Root cause was mixed ownership inside the live checkout. Restoring `deploy` ownership on the repo and reapplying log ACLs fixed the infrastructure-state drift.
