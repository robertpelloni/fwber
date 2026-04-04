# MEMORY.md

## 2026-04-04 — v1.5.7 Hetzner Script Executable Bits
- Live server pulls exposed that correct script contents are not enough if git tracks the files as non-executable while the deploy flow checks `-x` before invoking them.
- Marking the Hetzner ops scripts executable in git is a small but important packaging fix for reliable server-side automation.

## 2026-04-04 — v1.5.6 WebSocket Smoke Handshake Fix
- The first full public Hetzner smoke run revealed a probe-level websocket bug, not a service-level failure: the smoke script was sending an invalid `Sec-WebSocket-Key`, while manual testing proved the websocket stack was healthy.
- Fixing the probe was necessary before trusting the full public smoke summary after cutover.

## 2026-04-04 — v1.5.5 Deploy Script Privilege Hardening
- A real deploy uncovered that the scripted build path was fine, but `systemctl` operations failed when the deploy flow was invoked from the `deploy` account instead of root.
- The simplest fix was to make `deploy-backend.sh` auto-detect non-root execution and prefix service operations with `sudo` when needed.
