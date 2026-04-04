# MEMORY.md

## 2026-04-04 — v1.5.0 Endpoint Fingerprints & Host Signals
- Once smoke reports had diagnostics, the next missing piece was endpoint fingerprinting: remote IP, server header, redirect target, and body excerpt per check.
- The latest live smoke run makes the current drift much more concrete: `api.fwber.me` health failures are Apache-served from `75.119.202.57`, while `geo.fwber.me` failures are Vercel-served from `64.29.17.1`.
- Normalizing empty snapshot fields to `—` is necessary because Bash tab-delimited parsing becomes unreliable when optional fields are blank.

## 2026-04-04 — v1.4.9 Smoke Check Diagnostics & Remediation Hints
- Raw smoke-check evidence is useful, but operators move faster when the report translates common failure patterns into probable causes and next actions.
- The live fwber domain responses are now specific enough to support lightweight heuristics: the health-route 404 cluster strongly suggests a stale backend rollout, while the geo-domain Vercel message strongly suggests DNS/proxy drift.
- Auth and roast continuing to pass while health/geo fail is an important narrowing signal; it indicates partial live health rather than a full-stack outage.

## 2026-04-04 — v1.4.8 Smoke Check Report Artifacts & Live Drift Detection
- A smoke-check script is more useful when it leaves behind machine-readable and human-readable evidence, not just terminal output.
- Timestamped deploy-report directories are a good default because they preserve cutover history without forcing operators to invent filenames during an incident.
- Running the smoke check against the real public domains immediately surfaced concrete drift: `api.fwber.me` is not yet serving the new `/api/health*` routes, and `geo.fwber.me` is currently misrouted or undeployed.
- The current public contract is partially healthy rather than fully broken: frontend reachability, invalid-login handling, and public roast preview still pass.
