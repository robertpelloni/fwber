# MEMORY.md

## 2026-04-05 — v1.7.2 Live Hetzner repo ownership drift can break an otherwise healthy deploy pipeline
- A push-triggered Hetzner deploy failed with `insufficient permission for adding an object to repository database .git/objects` even though the workflow and app code were otherwise healthy.
- Root cause was mixed ownership inside the live checkout. Restoring `deploy` ownership on the repo and reapplying log ACLs fixed the infrastructure-state drift.

## 2026-04-05 — v1.7.1 Dead linked frontend surfaces are fast restoration wins
- `/activity`, `/notifications`, and `/settings/travel` were still referenced by the live signed-in shell even though the routes were missing.
- Restoring these lightweight pages is a high-leverage way to make the app feel far more complete without reopening the most complex archived systems first.
