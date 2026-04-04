# MEMORY.md

## 2026-04-04 — v1.5.8 Restored Feature Navigation Surface
- The user feedback was correct: several restored sections already existed in code, but they were not meaningfully visible from the signed-in app shell.
- Restoration is not complete from a user perspective unless the restored routes are surfaced in navigation and dashboard entry points.

## 2026-04-04 — v1.5.7 Hetzner Script Executable Bits
- Live server pulls exposed that correct script contents are not enough if git tracks the files as non-executable while the deploy flow checks `-x` before invoking them.
- Marking the Hetzner ops scripts executable in git is a small but important packaging fix for reliable server-side automation.

## 2026-04-04 — v1.5.6 WebSocket Smoke Handshake Fix
- The first full public Hetzner smoke run revealed a probe-level websocket bug, not a service-level failure: the smoke script was sending an invalid `Sec-WebSocket-Key`, while manual testing proved the websocket stack was healthy.
- Fixing the probe was necessary before trusting the full public smoke summary after cutover.
