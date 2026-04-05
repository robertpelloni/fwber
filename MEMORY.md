# MEMORY.md

## 2026-04-05 — v1.8.3 the latest Hetzner deploy failure was not a code/runtime failure, it was a privilege-shape mismatch
- GitHub Hetzner deploy reached `composer install`, migration execution, optimize, and `php artisan deploy:verify` successfully.
- The failure occurred afterward when the script tried `sudo cp` / `sudo ln` to refresh nginx configs under `/etc/nginx`, which the deploy user could not perform non-interactively.
- The deploy script now treats nginx config sync as optional in that privilege shape while keeping required `nginx -t` and `systemctl` operations strict.

## 2026-04-05 — v1.8.2 referrals and video were still latent in the frontend, so backend restoration had very high leverage
- The product still had referral banners, register-time referral handling, vouch links, wallet payout expectations, and a fairly complete WebRTC video UI.
- The main missing pieces were the backend/API contracts and several frontend callers still using stale URL assumptions.
- Restoring these flows was therefore more valuable than inventing new UI because large user-visible surfaces were already waiting behind dead or malformed endpoints.

## 2026-04-05 — v1.8.1 Wallet was the next obvious dead-route cluster after Events
- `/wallet` was still referenced by event payment, notification gift routes, and premium filter upsells, while a wallet hook already existed in the frontend.
- Restoring a compact wallet backend/API/page is another strong leverage move because it resolves several visible dead links and reuses existing frontend logic without reintroducing the full archived token economy.
