# MEMORY.md

## 2026-04-04 — v1.5.9 Live Dashboard API + Realtime Recovery
- The live console traces were accurate: the frontend was still sending browser requests to Vercel-relative `/api/*` paths, which do not proxy to the Laravel backend in production.
- Realtime also needed production-host fallbacks because env drift on Vercel can leave Reverb looking unconfigured even when `ws.fwber.me` is healthy.
- Dashboard endpoints existed in the controller but were missing from `routes/api.php`, so frontend + backend had drifted out of contract.

## 2026-04-04 — v1.5.8 Restored Feature Navigation Surface
- The user feedback was correct: several restored sections already existed in code, but they were not meaningfully visible from the signed-in app shell.
- Restoration is not complete from a user perspective unless the restored routes are surfaced in navigation and dashboard entry points.

## 2026-04-04 — v1.5.7 Hetzner Script Executable Bits
- Live server pulls exposed that correct script contents are not enough if git tracks the files as non-executable while the deploy flow checks `-x` before invoking them.
- Marking the Hetzner ops scripts executable in git is a small but important packaging fix for reliable server-side automation.
