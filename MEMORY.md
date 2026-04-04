# MEMORY.md

## 2026-04-04 — v1.5.6 WebSocket Smoke Handshake Fix
- The first full public Hetzner smoke run revealed a probe-level websocket bug, not a service-level failure: the smoke script was sending an invalid `Sec-WebSocket-Key`, while manual testing proved the websocket stack was healthy.
- Fixing the probe was necessary before trusting the full public smoke summary after cutover.

## 2026-04-04 — v1.5.5 Deploy Script Privilege Hardening
- A real deploy uncovered that the scripted build path was fine, but `systemctl` operations failed when the deploy flow was invoked from the `deploy` account instead of root.
- The simplest fix was to make `deploy-backend.sh` auto-detect non-root execution and prefix service operations with `sudo` when needed.

## 2026-04-04 — v1.5.4 Hetzner Backend Execution & Database Migration
- The Hetzner fwber backend runtime is now genuinely deployed, not just planned: repo cloned, dependencies installed, Rust upgraded, geo built, local MySQL provisioned, DreamHost DB imported, and queue/reverb/geo services enabled.
- DreamHost MySQL was not a good long-term dependency for the Hetzner backend path; migrating fwber DB state locally to Hetzner was the correct simplification move.
- `ws.fwber.me` is already functioning correctly on Hetzner, while `api.fwber.me` and `geo.fwber.me` are now primarily public DNS/TLS cutover problems rather than application deployment problems.
