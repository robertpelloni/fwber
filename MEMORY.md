# MEMORY.md

## 2026-04-04 — v1.4.8 Smoke Check Report Artifacts & Live Drift Detection
- A smoke-check script is more useful when it leaves behind machine-readable and human-readable evidence, not just terminal output.
- Timestamped deploy-report directories are a good default because they preserve cutover history without forcing operators to invent filenames during an incident.
- Running the smoke check against the real public domains immediately surfaced concrete drift: `api.fwber.me` is not yet serving the new `/api/health*` routes, and `geo.fwber.me` is currently misrouted or undeployed.
- The current public contract is partially healthy rather than fully broken: frontend reachability, invalid-login handling, and public roast preview still pass.

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
