# MEMORY.md

## 2026-04-04 — v1.5.4 Hetzner Backend Execution & Database Migration
- The Hetzner fwber backend runtime is now genuinely deployed, not just planned: repo cloned, dependencies installed, Rust upgraded, geo built, local MySQL provisioned, DreamHost DB imported, and queue/reverb/geo services enabled.
- DreamHost MySQL was not a good long-term dependency for the Hetzner backend path; migrating fwber DB state locally to Hetzner was the correct simplification move.
- `ws.fwber.me` is already functioning correctly on Hetzner, while `api.fwber.me` and `geo.fwber.me` are now primarily public DNS/TLS cutover problems rather than application deployment problems.

## 2026-04-04 — v1.5.3 Smoke Report Notification Publisher
- Once smoke reports had drift diffing, the next missing layer was a concise publishable summary that operators could post to chat or webhook systems without reading the full artifact bundle.
- A dedicated publisher script is cleaner than trying to overload the drift comparator with notification formatting and webhook responsibilities.
- The current pipeline can now generate three report layers in sequence: full smoke summary, drift diff, and compact notification summary.

## 2026-04-04 — v1.5.2 Smoke Report Drift Diff
- Once smoke reports had DNS, fingerprints, and diagnostics, the next missing capability was historical comparison between deploys.
- A dedicated compare script is simpler and safer than trying to overload the smoke-check script itself with previous/current state management.
- Drift summaries are especially useful when the environment is stable, because they can prove that repeated smoke runs are consistent and no new routing changes slipped in silently.
