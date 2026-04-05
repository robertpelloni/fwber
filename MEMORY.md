# MEMORY.md

## 2026-04-05 — v1.6.7 Frontend CI still depended on GitHub's older npm stack
- After resyncing the frontend lockfile, GitHub frontend builds still failed while local `npm ci` passed.
- Root cause was toolchain family drift: local lockfile regeneration/validation happened on Node 24 + npm 11, while GitHub frontend builds were still pinned to Node 20 + npm 10.
- Aligning the workflow to Node 24 is the cleanest way to remove that mismatch and also future-proofs against the upcoming Node 20 action deprecation.

## 2026-04-05 — v1.6.6 Daily-log shared write should use ACLs, not Monolog chmod from deploy user
- The first live deploy after the backend stability patch failed because Monolog's `permission` option tried to chmod a daily log file owned by `www-data` from a deploy-user artisan process.
- The correct repair shape is shared ACLs on `storage/logs` plus removing the Monolog permission override, not repeated per-file chmod attempts in deploy code.
