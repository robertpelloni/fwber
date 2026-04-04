# MEMORY.md

## 2026-04-04 — v1.4.0 Marketplace & Merchant Restoration
- The merchant restore worked best when rebuilt as a compact commerce slice: merchant profile, inventory, storefront, payment ledger, and redemption flow, without reviving the entire historical promotion economy.
- Reusing the compact payment gateway pattern from premium restoration keeps merchant purchases testable in mock mode and production-ready for Stripe later.
- Merchant navigation needed active-route cleanup because the surviving `MerchantHeader` still pointed to archived promotion pages.
- AR inventory browsing should consume the restored nearby marketplace API, even if exact merchant geo persistence is deferred to a later phase.

## 2026-04-04 — v1.3.9 Premium & Billing Restoration
- The cleanest way to restore premium after the simplification was to rebuild a compact billing slice rather than reactivating every archived economy/referral/token subsystem.
- Mock gateway support is crucial for phased restoration because it keeps billing UX testable locally without live Stripe credentials.
- Premium-gated pages should not rely on the generic API client when `403` means "upgrade required" rather than "session expired"; otherwise premium locks can accidentally trigger logout redirects.
- A minimal `payments` + `subscriptions` ledger is enough to restore status/history/settings pages and webhook reconciliation without bringing back the full token economy.
