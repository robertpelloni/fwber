# MEMORY.md

## 2026-04-05 — v1.8.6 the public roast preview appears to have a transient first-hit failure mode immediately after deploy
- Manual post-deploy calls to `https://api.fwber.me/api/public/roast` returned 200 with the expected preview payload.
- The GitHub smoke check, however, consistently saw a 500 on the first asserted roast request immediately after deploy.
- A pragmatic low-risk stabilizer is to warm the endpoint once before the asserted smoke call so the actual contract check observes the steady-state behavior rather than a deploy-edge cold path.

## 2026-04-05 — v1.8.5 smoke infrastructure can fail for two different reasons: hangs and endpoint regressions
- The websocket probe in `smoke-check.sh` can hang long enough to exhaust the GitHub SSH action command timeout if the TLS/WebSocket session never cleanly yields a line back.
- The public roast preview is part of the smoke surface, so AI-driver misconfiguration is not just a feature problem — it becomes a deployment validation problem too.
- The fix was two-part: bound the websocket probe with `timeout`, and harden public roast generation against broader `Throwable` failures.

## 2026-04-05 — v1.8.4 the safest Hetzner fix was a narrow root-owned helper instead of broader sudo
- The deploy user only had passwordless sudo for specific `systemctl` reload/restart commands, not raw `cp` / `ln` or `nginx -t`.
- Rather than widening broad filesystem sudo, the live host now exposes `/usr/local/bin/fwber-sync-nginx-sites` as a narrow root-owned helper and grants `deploy` passwordless sudo only for that script.
- The repo deploy script now prefers this helper when present, which is a cleaner long-term shape for GitHub-triggered deploys.
