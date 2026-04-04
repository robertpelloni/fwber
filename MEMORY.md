# MEMORY.md

## 2026-04-04 — v1.5.3 Smoke Report Notification Publisher
- Once smoke reports had drift diffing, the next missing layer was a concise publishable summary that operators could post to chat or webhook systems without reading the full artifact bundle.
- A dedicated publisher script is cleaner than trying to overload the drift comparator with notification formatting and webhook responsibilities.
- The current pipeline can now generate three report layers in sequence: full smoke summary, drift diff, and compact notification summary.

## 2026-04-04 — v1.5.2 Smoke Report Drift Diff
- Once smoke reports had DNS, fingerprints, and diagnostics, the next missing capability was historical comparison between deploys.
- A dedicated compare script is simpler and safer than trying to overload the smoke-check script itself with previous/current state management.
- Drift summaries are especially useful when the environment is stable, because they can prove that repeated smoke runs are consistent and no new routing changes slipped in silently.

## 2026-04-04 — v1.5.1 DNS Resolution Appendix & Host Mapping
- After endpoint fingerprints, the next missing evidence layer was hostname resolution itself; without that, operators still had to run separate DNS tools after reading the smoke report.
- The current live smoke run now shows `geo.fwber.me` resolving to `216.198.79.65|64.29.17.1`, which strengthens the case that the geo hostname is still tied to the wrong hosting topology.
- Using Python's `socket.getaddrinfo` was the most portable way to add DNS resolution without making `dig` or `nslookup` hard dependencies of the smoke-check script.
