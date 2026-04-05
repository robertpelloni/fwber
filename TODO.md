# TODO — fwber Immediate Action Items

> **Version:** 1.9.3
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Next replay targets should include deployment health endpoints, smoke checks, drift diagnostics, workflow stabilization, ACL/log fixes, and route/schema-drift protections.
- [ ] **Validate Restore Branch After First Replay Pass**: Run backend tests, frontend build, deploy verification, and smoke-check syntax validation on `restore/pre-simplification-hetzner` after the next batch of replays.
- [ ] **Resolve Infra/Runtime Conflicts Early**: Prioritize conflicts involving workflows, env contracts, logging permissions, route caching, and health endpoint expectations before replaying deeper app-layer fixes.

## 🔴 Critical: Product Recovery
- [ ] **Preserve Full Feature Surface On Restore Branch**: Keep using the rewind branch as the recovery vehicle for removed systems instead of re-pruning them during conflict resolution.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live drift protections.

## 🟡 High: Verification
- [ ] **Replay Remaining Mandatory Infra Commits Listed In `ops/git/hetzner-replay-commits.txt`**
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Started Actual Hetzner Replay On The Restore Branch**: Cherry-picked `11250c5ec` and `59f132e38` onto `restore/pre-simplification-hetzner` and pushed branch tip `96c10825f`.
- [x] **Created Dedicated Rewind Restoration Track**: Identified baseline `a636a53c3`, documented the rewind strategy, created helper tooling, and pushed `restore/pre-simplification-hetzner`.
- [x] **Premium Discovery Filter Restoration**: Restored premium discovery schema, profile persistence, `/matches` premium filter passthrough, token gating, and expanded match filter UI.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
