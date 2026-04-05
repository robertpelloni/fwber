# TODO — fwber Immediate Action Items

> **Version:** 1.9.5
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Inspect Latest Restore-Branch Backend CI Run**: Check whether direct fix `d4d073e4f` removes the `Api`/`API` route-resolution failures and identify the next restore-branch backend breakpoints.
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Next replay targets should include executable-bit tracking, roast-preview smoke handling, remaining deploy privilege fixes, and any still-missing route/schema drift protections.
- [ ] **Validate Restore Branch After This Infra Tranche**: Run backend tests, frontend build, deploy verification, and smoke-check syntax validation on `restore/pre-simplification-hetzner` after the next replay batch.

## 🔴 Critical: Product Recovery
- [ ] **Preserve Full Feature Surface On Restore Branch**: Keep using the rewind branch as the recovery vehicle for removed systems instead of re-pruning them during conflict resolution.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live drift protections.

## 🟡 High: Verification
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Run Restore-Branch Local Validation Once Dependencies Are Installed There**: local `php artisan test` in the worktree currently cannot run until vendor dependencies exist in that checkout.
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Replayed Health/Smoke/Deploy Hardening Onto Restore Branch**: The restore branch now includes deployment health, smoke tooling, smoke reports, ACL/logging, and nginx/deploy hardening commits through tip `cb8ac70ca`.
- [x] **Fixed Restore-Branch Linux Route Namespace Casing**: Added direct restore-branch commit `d4d073e4f` correcting `App\Http\Controllers\API\...` route references to `Api\...` for real Linux CI compatibility.
- [x] **Replayed Workflow Modernization Onto Restore Branch**: Cherry-picked `847f43f26`, `18f3539e9`, `81781ffb1`, `6f1251b18`, and `e0fee531a` so the rewind branch carries the modern Hetzner deploy and frontend workflow stack.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
