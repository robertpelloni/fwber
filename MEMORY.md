# MEMORY.md

## 2026-04-05 — v1.6.6 Daily-log shared write should use ACLs, not Monolog chmod from deploy user
- The first live deploy after the backend stability patch failed because Monolog's `permission` option tried to chmod a daily log file owned by `www-data` from a deploy-user artisan process.
- The correct repair shape is shared ACLs on `storage/logs` plus removing the Monolog permission override, not repeated per-file chmod attempts in deploy code.

## 2026-04-04 — v1.6.5 Hetzner backend drift was app/schema inconsistency, not dead infrastructure
- Direct Hetzner inspection showed the fwber backend stack itself was alive: nginx, php-fpm, queue worker, Reverb, geo service, Redis, and MySQL were all up.
- The live failures were caused by application drift instead: the root route rendered a missing `welcome` view, `php artisan route:list` broke on a missing `WebFingerController`, and dashboard activity crashed because the `user_matches` table was missing even though migrations claimed it had already run.
