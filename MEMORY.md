# MEMORY.md

## 2026-04-04 — v1.5.2 Smoke Report Drift Diff
- Once smoke reports had DNS, fingerprints, and diagnostics, the next missing capability was historical comparison between deploys.
- A dedicated compare script is simpler and safer than trying to overload the smoke-check script itself with previous/current state management.
- Drift summaries are especially useful when the environment is stable, because they can prove that repeated smoke runs are consistent and no new routing changes slipped in silently.

## 2026-04-04 — v1.5.1 DNS Resolution Appendix & Host Mapping
- After endpoint fingerprints, the next missing evidence layer was hostname resolution itself; without that, operators still had to run separate DNS tools after reading the smoke report.
- The current live smoke run now shows `geo.fwber.me` resolving to `216.198.79.65|64.29.17.1`, which strengthens the case that the geo hostname is still tied to the wrong hosting topology.
- Using Python's `socket.getaddrinfo` was the most portable way to add DNS resolution without making `dig` or `nslookup` hard dependencies of the smoke-check script.

## 2026-04-04 — v1.5.0 Endpoint Fingerprints & Host Signals
- Once smoke reports had diagnostics, the next missing piece was endpoint fingerprinting: remote IP, server header, redirect target, and body excerpt per check.
- The latest live smoke run makes the current drift much more concrete: `api.fwber.me` health failures are Apache-served from `75.119.202.57`, while `geo.fwber.me` failures are Vercel-served from `64.29.17.1`.
- Normalizing empty snapshot fields to `—` is necessary because Bash tab-delimited parsing becomes unreliable when optional fields are blank.
