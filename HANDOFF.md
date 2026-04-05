# HANDOFF - End of GPT Session

> **Timestamp:** 2026-04-05
> **Version Reached:** 1.6.6
> **Current Model:** GPT

## Executive Summary
After deploying the Hetzner backend stability patch, the next failure surfaced immediately in the real GitHub deploy path: daily log ownership drift between `deploy` and `www-data`.

This session fixed that in **v1.6.6 "Hetzner Log ACL Deploy Fix"**.

---

## What Happened
### 1. Live backend drift patch exposed a new deploy blocker
The `v1.6.5` push reached Hetzner and started deploying correctly, but the GitHub deploy failed during the migration/logging phase because Laravel/Monolog attempted to chmod a daily log file owned by `www-data`.

Observed failure shape:
- deploy runs as `deploy`
- PHP-FPM/web runtime writes daily logs as `www-data`
- Monolog permission handling tried to chmod the current day log from the deploy-side artisan process
- deploy failed with `chmod(): Operation not permitted`

### 2. Root cause
The previous attempt to harden logging by setting Monolog `permission => 0664` was the wrong shape for this environment.

Why:
- it assumes the process opening the log file can chmod it
- that is false when the file was created by a different runtime user (`www-data`) and the deploy process runs as `deploy`

### 3. Correct repair chosen
The correct fix is shared write access at the directory ACL layer, not repeated per-file chmod from application logging config.

So I:
- removed the Monolog permission override from `config/logging.php`
- changed the deploy script to maintain ACLs on `storage/logs`
- applied the matching ACL repair live on Hetzner

---

## What Was Changed

### Repo changes
Updated:
- `fwber-backend/config/logging.php`
- `ops/hetzner/scripts/deploy-backend.sh`
- documentation/version files

Behavior now:
- no Monolog chmod attempt against foreign-owned daily logs
- deploy script uses `setfacl` on `storage/logs` when available so future rotated log files inherit shared access for:
  - `deploy`
  - `www-data`

### Live server changes
Executed on Hetzner:
- added `deploy` to `www-data` group
- fixed existing log file permissions to group-writable
- applied ACLs to `/var/www/fwber/repo/fwber-backend/storage/logs`
- applied default ACLs so future files inherit shared access

Confirmed ACL state now includes shared entries for:
- `user:deploy:rwx`
- `group:www-data:rwx`
- matching default ACLs

---

## Validation State
### Confirmed before this fix
- backend CI green
- repository hygiene green
- GitHub Hetzner backend deploy green in prior validated runs
- direct backend drift fixes compile/test locally

### Remaining next verification after this release
- re-run Hetzner backend deploy on the new ACL-aware script/code
- verify live root route and dashboard endpoints stop 500ing
- re-run frontend GitHub build after lockfile resync

---

## Files Changed in This Slice
- `fwber-backend/config/logging.php`
- `ops/hetzner/scripts/deploy-backend.sh`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `TODO.md`
- `MEMORY.md`
- `ROADMAP.md`
- `DEPLOY.md`
- `HANDOFF.md`
- version files

---

## Git / Release
- **Target Version:** `1.6.6`
- **Recommended Commit Message:** `fix: replace log chmod strategy with acl-based hetzner deploy access (v1.6.6)`

---

## Best Next Steps
1. Commit and push `v1.6.6`
2. Re-run Hetzner backend deploy workflow
3. Re-verify live:
   - `https://api.fwber.me/`
   - dashboard routes
   - route tooling
4. Re-run frontend GitHub workflow
5. Continue production 500 sweep before large-scale feature restoration

No processes were manually killed.
