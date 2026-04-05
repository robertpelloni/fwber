# TODO — fwber Immediate Action Items

> **Version:** 1.9.4
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Next replay targets should include deployment health endpoints, smoke checks, drift diagnostics, ACL/log fixes, rustup path fixes, and live route/schema-drift protections.
- [ ] **Inspect Fresh Restore-Branch Workflow Runs**: Confirm whether replayed workflow stabilization resolves the earlier frontend build failure caused by stale workflow assumptions.
- [ ] **Validate Restore Branch After This Infra Tranche**: Run backend tests, frontend build, deploy verification, and smoke-check syntax validation on `restore/pre-simplification-hetzner` after the next replay batch.

## 🔴 Critical: Product Recovery
- [ ] **Preserve Full Feature Surface On Restore Branch**: Keep using the rewind branch as the recovery vehicle for removed systems instead of re-pruning them during conflict resolution.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live drift protections.

## 🟡 High: Verification
- [ ] **Replay Remaining Mandatory Infra Commits Listed In `ops/git/hetzner-replay-commits.txt`**
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Replayed Workflow Modernization Onto Restore Branch**: Cherry-picked `847f43f26` and `18f3539e9` so the rewind branch now has the Hetzner backend deploy workflow and stabilized CI workflow set.
- [x] **Started Actual Hetzner Replay On The Restore Branch**: Cherry-picked `11250c5ec` and `59f132e38` onto `restore/pre-simplification-hetzner`.
- [x] **Created Dedicated Rewind Restoration Track**: Identified baseline `a636a53c3`, documented the rewind strategy, created helper tooling, and pushed `restore/pre-simplification-hetzner`.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
