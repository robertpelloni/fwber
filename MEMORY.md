# MEMORY.md

## 2026-04-04 — v1.6.0 GitHub backend deploy workflow was still pointed at DreamHost
- The live backend has already been successfully deploying on Hetzner through SSH and `ops/hetzner/scripts/deploy-backend.sh`.
- However, `.github/workflows/deploy-backend.yml` was still configured to SSH into DreamHost, which meant CI automation had drifted behind the real infrastructure cutover.
- The workflow has now been rewritten to target Hetzner instead.

## 2026-04-04 — v1.5.9 Live Dashboard API + Realtime Recovery
- The live console traces were accurate: the frontend was still sending browser requests to Vercel-relative `/api/*` paths, which do not proxy to the Laravel backend in production.
- Realtime also needed production-host fallbacks because env drift on Vercel can leave Reverb looking unconfigured even when `ws.fwber.me` is healthy.
- Dashboard endpoints existed in the controller but were missing from `routes/api.php`, so frontend + backend had drifted out of contract.
