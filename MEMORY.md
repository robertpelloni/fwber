# MEMORY.md

## 2026-04-05 — v1.7.0 Friends restoration was a high-value next step because dead routes were already in the product shell
- `/friends` was still referenced by messages, notifications, activity, and Cypress coverage even though the route and backend surface were gone.
- Restoring Friends yields disproportionate value because it converts several dead navigation paths back into a coherent social feature without reopening the heaviest archived systems first.

## 2026-04-05 — v1.6.10 A dead public surface is worse than an explicitly retired one
- `mercure.fwber.me` was still proxying to a nonexistent local upstream and returning `502`, which falsely suggested an active but broken service.
- Since Reverb is the actual live realtime stack, the honest operational move is to retire the Mercure hostname explicitly with a tracked nginx response until/unless Mercure is intentionally brought back.
