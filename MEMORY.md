# MEMORY.md

## 2026-04-04 — v1.5.5 Deploy Script Privilege Hardening
- A real deploy uncovered that the scripted build path was fine, but `systemctl` operations failed when the deploy flow was invoked from the `deploy` account instead of root.
- The simplest fix was to make `deploy-backend.sh` auto-detect non-root execution and prefix service operations with `sudo` when needed.

## 2026-04-04 — v1.5.4 Hetzner Backend Execution & Database Migration
- The Hetzner fwber backend runtime is now genuinely deployed, not just planned: repo cloned, dependencies installed, Rust upgraded, geo built, local MySQL provisioned, DreamHost DB imported, and queue/reverb/geo services enabled.
- DreamHost MySQL was not a good long-term dependency for the Hetzner backend path; migrating fwber DB state locally to Hetzner was the correct simplification move.
- `ws.fwber.me` is already functioning correctly on Hetzner, while `api.fwber.me` and `geo.fwber.me` are now primarily public DNS/TLS cutover problems rather than application deployment problems.

## 2026-04-04 — v1.5.3 Smoke Report Notification Publisher
- Once smoke reports had drift diffing, the next missing layer was a concise publishable summary that operators could post to chat or webhook systems without reading the full artifact bundle.
- A dedicated publisher script is cleaner than trying to overload the drift comparator with notification formatting and webhook responsibilities.
- The current pipeline can now generate three report layers in sequence: full smoke summary, drift diff, and compact notification summary.
