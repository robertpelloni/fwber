# MEMORY.md

## 2026-04-04 — v1.4.1 Hetzner Deployment Docs Refresh
- Once AI, premium, and merchant systems were restored, the old DreamHost-first deployment docs became actively misleading; deployment documentation needed to be treated as a product feature, not an afterthought.
- The right ops story for the current stack is Vercel frontend + Hetzner VPS backend, with DreamHost kept only as legacy historical reference.
- Stripe ops guidance also had to expand from premium-only assumptions to cover restored merchant marketplace purchases.

## 2026-04-04 — v1.4.0 Marketplace & Merchant Restoration
- The merchant restore worked best when rebuilt as a compact commerce slice: merchant profile, inventory, storefront, payment ledger, and redemption flow, without reviving the entire historical promotion economy.
- Reusing the compact payment gateway pattern from premium restoration keeps merchant purchases testable in mock mode and production-ready for Stripe later.
- Merchant navigation needed active-route cleanup because the surviving `MerchantHeader` still pointed to archived promotion pages.
- AR inventory browsing should consume the restored nearby marketplace API, even if exact merchant geo persistence is deferred to a later phase.
