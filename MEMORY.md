# MEMORY.md

## 2026-04-04 — v1.5.1 DNS Resolution Appendix & Host Mapping
- After endpoint fingerprints, the next missing evidence layer was hostname resolution itself; without that, operators still had to run separate DNS tools after reading the smoke report.
- The current live smoke run now shows `geo.fwber.me` resolving to `216.198.79.65|64.29.17.1`, which strengthens the case that the geo hostname is still tied to the wrong hosting topology.
- Using Python's `socket.getaddrinfo` was the most portable way to add DNS resolution without making `dig` or `nslookup` hard dependencies of the smoke-check script.

## 2026-04-04 — v1.5.0 Endpoint Fingerprints & Host Signals
- Once smoke reports had diagnostics, the next missing piece was endpoint fingerprinting: remote IP, server header, redirect target, and body excerpt per check.
- The latest live smoke run makes the current drift much more concrete: `api.fwber.me` health failures are Apache-served from `75.119.202.57`, while `geo.fwber.me` failures are Vercel-served from `64.29.17.1`.
- Normalizing empty snapshot fields to `—` is necessary because Bash tab-delimited parsing becomes unreliable when optional fields are blank.

## 2026-04-04 — v1.4.9 Smoke Check Diagnostics & Remediation Hints
- Raw smoke-check evidence is useful, but operators move faster when the report translates common failure patterns into probable causes and next actions.
- The live fwber domain responses are now specific enough to support lightweight heuristics: the health-route 404 cluster strongly suggests a stale backend rollout, while the geo-domain Vercel message strongly suggests DNS/proxy drift.
- Auth and roast continuing to pass while health/geo fail is an important narrowing signal; it indicates partial live health rather than a full-stack outage.
