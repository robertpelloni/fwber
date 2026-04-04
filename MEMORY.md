# MEMORY.md

## 2026-04-04 — v1.4.6 Deployment Health & Verification Surface
- Once the requested restoration waves were complete, the highest-value remaining repo-native task was deployment verification, not another UI feature.
- A stable `/api/health*` surface plus `php artisan deploy:verify` creates a much better bridge between local confidence and real Hetzner cutover execution.
- Health logic should live in one place; centralizing it in `HealthStatusService` prevents HTTP and CLI verification from drifting apart.
- Redis health should only be considered critical when the active runtime configuration actually depends on Redis-backed services; otherwise health checks generate noisy false negatives.

## 2026-04-04 — v1.4.5 Merchant Review Prioritization
- Merchant moderation became much more usable once the queue exposed search, priority, and faster note handling.
- After trust scoring, the real bottleneck was no longer ranking logic but moderator throughput.
- Priority scoring based on pending state, commerce evidence, profile completeness, and trust score is a practical bridge between a simple queue and a heavier future review-automation system.

## 2026-04-04 — v1.4.4 Merchant Trust Scoring & Moderation
- Merchant proximity alone was not enough for a credible marketplace restore; adding a compact trust score based on verification, completeness, inventory depth, and fulfilled commerce evidence made nearby ranking materially better.
- Restoring merchant trust also exposed that the moderation page already existed in the frontend but lacked active API routes; wiring those routes back in created a much more coherent moderation story.
- Direct axios-based API modules need special care when the canonical frontend env contract is `NEXT_PUBLIC_API_URL` without `/api`; moderation API handling was hardened accordingly.
