# MEMORY.md

## 2026-04-05 — v1.6.8 Discovery routes still need schema guards even when federation is de-scoped
- `/nodeinfo/2.0` was still 500ing live because `NodeInfoController` assumed `user_profiles.is_federated` existed.
- Even when federation is not the active product focus, discovery routes must degrade safely instead of crashing on absent optional schema.
- Frontend CI also needed runtime-family alignment: the lockfile/build was validated under Node 24 locally, so GitHub should match that instead of staying on Node 20.

## 2026-04-05 — v1.6.6 Daily-log shared write should use ACLs, not Monolog chmod from deploy user
- The first live deploy after the backend stability patch failed because Monolog's `permission` option tried to chmod a daily log file owned by `www-data` from a deploy-user artisan process.
- The correct repair shape is shared ACLs on `storage/logs` plus removing the Monolog permission override, not repeated per-file chmod attempts in deploy code.
