# TODO — fwber Immediate Action Items

> **Version:** 1.9.6
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Inspect Latest Restore-Branch CI Runs On `81ee89400`**: Confirm whether root-route/nodeinfo drift replay reduces the current backend/frontend failures and identify the next blockers.
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Next target commits should include executable-bit tracking and roast-preview smoke hardening, then shift toward the remaining route/schema/runtime fixes that still matter on the full-feature branch.
- [ ] **Address Remaining Restore-Branch Profile/Onboarding Drift**: Current backend CI evidence now points at profile update/onboarding/travel-mode persistence mismatches after the route-case fixes were resolved.

## 🔴 Critical: Product Recovery
- [ ] **Preserve Full Feature Surface On Restore Branch**: Keep using the rewind branch as the recovery vehicle for removed systems instead of re-pruning them during conflict resolution.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live drift protections.

## 🟡 High: Verification
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Run Restore-Branch Local Validation Once Dependencies Are Installed There**: local `php artisan test` in the worktree still requires dependency installation in that checkout.
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Replayed Route Drift Recovery Onto Restore Branch**: Cherry-picked `8357d83f3` and `9b090bf9b`, adding root-route recovery, dashboard drift migration/tests, and nodeinfo/frontend-CI alignment to the rewind branch.
- [x] **Replayed Health/Smoke/Deploy Hardening Onto Restore Branch**: The restore branch includes deployment health, smoke tooling, smoke reports, ACL/logging, and nginx/deploy hardening through the recent replay tranche.
- [x] **Fixed Restore-Branch Linux Route Namespace Casing**: Added direct restore-branch commit `d4d073e4f` correcting `Api` controller namespace casing for Linux CI compatibility.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
