# MEMORY.md

## 2026-04-04 — v1.4.3 Geo-Aware Merchant Ranking
- Merchant nearby discovery became substantially more real once merchant profiles owned their own coordinates instead of relying on fake relative offsets in the AR layer.
- The safest default for merchant location during onboarding is to inherit from the user's saved profile coordinates when the merchant does not provide storefront coordinates explicitly.
- AR overlays should consume actual API-returned coordinates whenever available; demo-relative offsets are acceptable only as a temporary stopgap during phased restoration.

## 2026-04-04 — v1.4.2 Hetzner Ops Templates & CI Env Alignment
- Deployment docs alone were not enough; the repo also needed copy-ready Nginx, systemd, and shell-script artifacts so provisioning can happen quickly and consistently.
- The frontend CI workflow and `.env.production.example` had drifted from the actual active runtime contract by still implying `/api`-suffixed base URLs and older websocket variable names.
- Aligning build-time env examples with real runtime expectations prevents subtle deployment regressions before the Hetzner box even goes live.
