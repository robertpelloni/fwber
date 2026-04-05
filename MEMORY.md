# MEMORY.md

## 2026-04-05 — v1.7.1 Dead linked frontend surfaces are fast restoration wins
- `/activity`, `/notifications`, and `/settings/travel` were still referenced by the live signed-in shell even though the routes were missing.
- Restoring these lightweight pages is a high-leverage way to make the app feel far more complete without reopening the most complex archived systems first.

## 2026-04-05 — v1.7.0 Friends restoration was a high-value next step because dead routes were already in the product shell
- `/friends` was still referenced from messages, notifications, activity, and Cypress coverage even though the route and backend surface were gone.
- Restoring Friends yields disproportionate value because it converts several dead navigation paths back into a coherent social feature without reopening the heaviest archived systems first.
