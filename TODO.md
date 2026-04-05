# TODO — fwber Immediate Action Items

> **Version:** 1.9.2
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Replay Mandatory Hetzner Infra Onto Restore Branch**: Cherry-pick or manually port the mandatory infrastructure/deploy/smoke/runtime commits listed in `ops/git/hetzner-replay-commits.txt` onto `restore/pre-simplification-hetzner`.
- [ ] **Validate Restore Branch After Infra Replay**: Run backend tests, frontend build, deploy verification, and smoke-check syntax validation on the restore branch.
- [ ] **Resolve Major Conflicts Between Full Feature Set And Modern Runtime Contract**: Pay special attention to websocket envs, frontend API origin handling, route cache safety, migration idempotency, and health endpoints.

## 🔴 Critical: Product Recovery
- [ ] **Restore Full Feature Surface Via Rewind Branch**: Use the pre-simplification branch as the main recovery vehicle for chatrooms, bulletin boards, groups, journals, audio rooms, venues, recommendation surfaces, councils, federation, and other removed systems.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live route/schema drift protections.

## 🟡 High: Verification
- [ ] **Compare Restore Branch To Current Main After First Replay Pass**: Identify which post-simplification fixes still need replay beyond the initial infra/runtime list.
- [ ] **Production 500 Error Sweep On Restore Branch**: Continue hardening real runtime behavior once the fuller feature surface is booting on the rewind branch.

## ✅ Recently Completed
- [x] **Created Dedicated Rewind Restoration Track**: Identified baseline `a636a53c3`, documented the rewind strategy, created helper tooling, and pushed `restore/pre-simplification-hetzner`.
- [x] **Premium Discovery Filter Restoration**: Restored premium discovery schema, profile persistence, `/matches` premium filter passthrough, token gating, and expanded match filter UI.
- [x] **Token-Gated Unlock Surface Restoration**: Restored match insights unlocks, private photo unlocks, generic content unlock ledger, and frontend locked/unlocked UX.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
