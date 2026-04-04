# MEMORY.md

## 2026-04-04 — v1.4.7 Hetzner Post-Deploy Smoke Checks
- After adding `/api/health*` and `php artisan deploy:verify`, the next missing layer was a shell-level smoke-check script that validates the public contract operators actually rely on during cutover.
- The most practical smoke checks are the ones that do not require secrets by default: frontend reachability, health endpoints, invalid-login behavior, public roast preview, and geo nearby lookups.
- Authenticated merchant/moderation/premium checks are valuable, but they should be opt-in through env tokens so the script remains usable before a full smoke-account strategy exists.
- A real websocket upgrade probe is much more meaningful than simply curling the websocket hostname; making it conditional on `FWBER_REVERB_APP_KEY` keeps the script useful in both low-context and high-context environments.

## 2026-04-04 — v1.4.6 Deployment Health & Verification Surface
- Once the requested restoration waves were complete, the highest-value remaining repo-native task was deployment verification, not another UI feature.
- A stable `/api/health*` surface plus `php artisan deploy:verify` creates a much better bridge between local confidence and real Hetzner cutover execution.
- Health logic should live in one place; centralizing it in `HealthStatusService` prevents HTTP and CLI verification from drifting apart.
- Redis health should only be considered critical when the active runtime configuration actually depends on Redis-backed services; otherwise health checks generate noisy false negatives.

## 2026-04-04 — v1.4.5 Merchant Review Prioritization
- Merchant moderation became much more usable once the queue exposed search, priority, and faster note handling.
- After trust scoring, the real bottleneck was no longer ranking logic but moderator throughput.
- Priority scoring based on pending state, commerce evidence, profile completeness, and trust score is a practical bridge between a simple queue and a heavier future review-automation system.
