# MEMORY.md

## 2026-04-04 — v1.4.5 Merchant Review Prioritization
- Once trust scoring existed, the next operator bottleneck was queue usability: moderators needed search, prioritization, and quick notes to make merchant review practical.
- The moderation dashboard already had enough structure to support a merchant queue, so extending it with priority and inline note editing was a high-leverage improvement.
- Search support across merchant name/category/location/address is a meaningful quality-of-life feature for real moderation operations.

## 2026-04-04 — v1.4.4 Merchant Trust Scoring & Moderation
- Merchant proximity alone was not enough for a credible marketplace restore; adding a compact trust score based on verification, completeness, inventory depth, and fulfilled commerce evidence made nearby ranking materially better.
- Restoring merchant trust also exposed that the moderation page already existed in the frontend but lacked active API routes; wiring those routes back in created a much more coherent moderation story.
- Direct axios-based API modules need special care when the canonical frontend env contract is `NEXT_PUBLIC_API_URL` without `/api`; moderation API handling was hardened accordingly.
