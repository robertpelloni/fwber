# MEMORY.md

## 2026-04-05 — v1.9.0 token-gated unlocks were another strong restoration because both backend hints and frontend expectations already existed
- The repo already had `ContentUnlockGate`, `PhotoUnlock`, `unlock_price` typing on photo payloads, and locked match-insights Cypress expectations.
- The real missing pieces were the controller/routes/schema plus frontend state handling for locked/unlocked insight responses.
- Restoring the unlock ledger as a compact shared backend service is a good pattern for remaining token-era spend surfaces.

## 2026-04-05 — v1.8.9 boosts were another latent token-spend cluster already waiting in the frontend
- The repo still had `BoostPurchaseModal`, boost hooks/api clients, Cypress boost tests, a `Boost` model, and a purchase request object.
- The missing pieces were the controller/routes/migration and a visible entry point in the active matches UI.
- Restoring boosts is a good pattern for the remaining token-era cleanup: keep the feature compact, make wallet debits explicit, and reconnect an already-existing frontend surface.

## 2026-04-05 — v1.8.8 gifts were still a live dead-surface cluster inside chat, notifications, and wallet routing
- The frontend still had `GiftShopModal`, gifts hooks/api clients, wallet gift routing, and gift notification normalization.
- The backend already retained `Gift`, `UserGift`, and request objects, so restoring compact gift endpoints and schema was another high-leverage restoration similar to referrals/video.
- Expanding `/wallet` with a real `?tab=gifts` view closes the loop for notifications and gives the restored gift flow an actual destination.
