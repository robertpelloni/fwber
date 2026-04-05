# MEMORY.md

## 2026-04-05 — v1.8.4 the safest Hetzner fix was a narrow root-owned helper instead of broader sudo
- The deploy user only had passwordless sudo for specific `systemctl` reload/restart commands, not raw `cp` / `ln` or `nginx -t`.
- Rather than widening broad filesystem sudo, the live host now exposes `/usr/local/bin/fwber-sync-nginx-sites` as a narrow root-owned helper and grants `deploy` passwordless sudo only for that script.
- The repo deploy script now prefers this helper when present, which is a cleaner long-term shape for GitHub-triggered deploys.

## 2026-04-05 — v1.8.3 the latest Hetzner deploy failure was not a code/runtime failure, it was a privilege-shape mismatch
- GitHub Hetzner deploy reached `composer install`, migration execution, optimize, and `php artisan deploy:verify` successfully.
- The failure occurred afterward when the script tried `sudo cp` / `sudo ln` to refresh nginx configs under `/etc/nginx`, which the deploy user could not perform non-interactively.
- The deploy script now treats nginx config sync as optional in that privilege shape while keeping required `nginx -t` and `systemctl` operations strict.

## 2026-04-05 — v1.8.2 referrals and video were still latent in the frontend, so backend restoration had very high leverage
- The product still had referral banners, register-time referral handling, vouch links, wallet payout expectations, and a fairly complete WebRTC video UI.
- The main missing pieces were the backend/API contracts and several frontend callers still using stale URL assumptions.
- Restoring these flows was therefore more valuable than inventing new UI because large user-visible surfaces were already waiting behind dead or malformed endpoints.
