# HANDOFF - End of GPT Session

> **Timestamp:** 2026-04-04
> **Version Reached:** 1.6.0
> **Current Model:** GPT

## Executive Summary
The answer to the user's question was:

- **Yes**, the backend is deploying correctly on **Hetzner** through the in-repo deploy path and manual SSH-driven execution.
- **Yes**, the GitHub Actions backend deploy workflow was still stale and still pointed at **DreamHost**.

This session fixed the automation drift in **v1.6.0 "GitHub Backend Deploy Switched to Hetzner"**.

---

## What I Verified

### Hetzner backend deployment status
Previously established and re-confirmed during the latest live ops work:
- Hetzner repo is live at `/var/www/fwber/repo`
- backend deploy script runs successfully there
- backend services restart correctly
- health endpoints are healthy
- current production backend version was successfully advanced on Hetzner through the in-repo deploy path

So the real backend deployment path is now Hetzner and it is working.

### GitHub Actions drift
Inspected:
- `.github/workflows/deploy-backend.yml`

It was still configured to:
- SSH into DreamHost
- use `DREAMHOST_HOST`, `DREAMHOST_USERNAME`, `DREAMHOST_SSH_KEY`
- run the old DreamHost deploy path

That meant CI automation had not caught up with the real infrastructure migration.

---

## What Was Changed
Updated:
- `.github/workflows/deploy-backend.yml`

Changes:
- renamed workflow to **Deploy Backend (Hetzner)**
- switched SSH target secrets to:
  - `HETZNER_HOST`
  - `HETZNER_USERNAME`
  - `HETZNER_SSH_KEY`
- added optional:
  - `HETZNER_PROJECT_PATH`
  - `HETZNER_REVERB_APP_KEY`
- changed the workflow to run:
  - `./ops/hetzner/scripts/deploy-backend.sh`
- added `workflow_dispatch`
- expanded trigger paths to include `ops/hetzner/**` and the workflow file itself

This brings GitHub backend automation in line with the actual production topology.

---

## Repo / Release Updates
Updated for `v1.6.0`:
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `TODO.md`
- `MEMORY.md`
- `ROADMAP.md`
- `DEPLOY.md`
- `HANDOFF.md`
- root/backend/frontend version files

---

## Git / Release
- **Target Version:** `1.6.0`
- **Recommended Commit Message:** `chore: switch github backend deploy workflow from dreamhost to hetzner (v1.6.0)`

---

## Important Follow-Up
The workflow file is now correct, but GitHub must still have the correct repository secrets configured:
- `HETZNER_HOST`
- `HETZNER_USERNAME`
- `HETZNER_SSH_KEY`
- optional `HETZNER_PROJECT_PATH`
- optional `HETZNER_REVERB_APP_KEY`

After those are set, the workflow should be manually triggered once to verify end-to-end CI deployment against Hetzner.
