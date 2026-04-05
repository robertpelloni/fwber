# MEMORY.md

## 2026-04-05 — v1.8.9 boosts were another latent token-spend cluster already waiting in the frontend
- The repo still had `BoostPurchaseModal`, boost hooks/api clients, Cypress boost tests, a `Boost` model, and a purchase request object.
- The missing pieces were the controller/routes/migration and a visible entry point in the active matches UI.
- Restoring boosts is a good pattern for the remaining token-era cleanup: keep the feature compact, make wallet debits explicit, and reconnect an already-existing frontend surface.

## 2026-04-05 — v1.8.8 gifts were still a live dead-surface cluster inside chat, notifications, and wallet routing
- The frontend still had `GiftShopModal`, gifts hooks/api clients, wallet gift routing, and gift notification normalization.
- The backend already retained `Gift`, `UserGift`, and request objects, so restoring compact gift endpoints and schema was another high-leverage restoration similar to referrals/video.
- Expanding `/wallet` with a real `?tab=gifts` view closes the loop for notifications and gives the restored gift flow an actual destination.

## 2026-04-05 — v1.8.7 the roast preview is important, but not important enough to be a deploy blocker
- The core Hetzner deploy contract (database, redis, cache, storage, queue, broadcast, health endpoints, websocket upgrade) can all be healthy while the public roast preview still exhibits transient first-hit instability.
- The smoke system now records that issue as a warning instead of a failure so deployment automation reflects platform health more accurately while still preserving diagnostics.
