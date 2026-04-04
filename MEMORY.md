# MEMORY.md

## 2026-04-04 — v1.4.9 Smoke Check Diagnostics & Remediation Hints
- Raw smoke-check evidence is useful, but operators move faster when the report translates common failure patterns into probable causes and next actions.
- The live fwber domain responses are now specific enough to support lightweight heuristics: the health-route 404 cluster strongly suggests a stale backend rollout, while the geo-domain Vercel message strongly suggests DNS/proxy drift.
- Auth and roast continuing to pass while health/geo fail is an important narrowing signal; it indicates partial live health rather than a full-stack outage.

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
