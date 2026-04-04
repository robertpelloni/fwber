# MEMORY.md

## 2026-04-04 — v1.4.2 Hetzner Ops Templates & CI Env Alignment
- Deployment docs alone were not enough; the repo also needed copy-ready Nginx, systemd, and shell-script artifacts so provisioning can happen quickly and consistently.
- The frontend CI workflow and `.env.production.example` had drifted from the actual active runtime contract by still implying `/api`-suffixed base URLs and older websocket variable names.
- Aligning build-time env examples with real runtime expectations prevents subtle deployment regressions before the Hetzner box even goes live.

## 2026-04-04 — v1.4.1 Hetzner Deployment Docs Refresh
- Once AI, premium, and merchant systems were restored, the old DreamHost-first deployment docs became actively misleading; deployment documentation needed to be treated as a product feature, not an afterthought.
- The right ops story for the current stack is Vercel frontend + Hetzner VPS backend, with DreamHost kept only as legacy historical reference.
- Stripe ops guidance also had to expand from premium-only assumptions to cover restored merchant marketplace purchases.
