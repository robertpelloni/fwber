# MEMORY.md

## 2026-04-04 — v1.4.4 Merchant Trust Scoring & Moderation
- Merchant proximity alone was not enough for a credible marketplace restore; adding a compact trust score based on verification, completeness, inventory depth, and fulfilled commerce evidence made nearby ranking materially better.
- Restoring merchant trust also exposed that the moderation page already existed in the frontend but lacked active API routes; wiring those routes back in created a much more coherent moderation story.
- Direct axios-based API modules need special care when the canonical frontend env contract is `NEXT_PUBLIC_API_URL` without `/api`; moderation API handling was hardened accordingly.

## 2026-04-04 — v1.4.3 Geo-Aware Merchant Ranking
- Merchant nearby discovery became substantially more real once merchant profiles owned their own coordinates instead of relying on fake relative offsets in the AR layer.
- The safest default for merchant location during onboarding is to inherit from the user's saved profile coordinates when the merchant does not provide storefront coordinates explicitly.
- AR overlays should consume actual API-returned coordinates whenever available; demo-relative offsets are acceptable only as a temporary stopgap during phased restoration.
