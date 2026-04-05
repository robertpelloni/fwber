# MEMORY.md

## 2026-04-05 — v1.6.9 Frontend CI needed a pragmatic install strategy, not more lockfile churn
- After lockfile resync and Node 24 alignment, the frontend GitHub workflow was still failing because of platform-sensitive optional dependency resolution in wallet/native-adjacent packages.
- Switching the CI install step to `npm install --no-fund --no-audit` is the pragmatic path to restore build verification signal while the dependency graph is further simplified.

## 2026-04-05 — v1.6.8 Discovery routes still need schema guards even when federation is de-scoped
- `/nodeinfo/2.0` was still 500ing live because `NodeInfoController` assumed `user_profiles.is_federated` existed.
- Even when federation is not the active product focus, discovery routes must degrade safely instead of crashing on absent optional schema.
