# TODO — fwber Immediate Action Items

> **Version:** 1.9.7
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Inspect Latest Restore-Branch CI Runs On `47b835225`**: Confirm whether the direct profile/frontend stabilization fixes reduce the current backend/frontend failures and identify the next blockers.
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Next target commits should include executable-bit tracking and roast-preview smoke hardening, then remaining route/schema/runtime fixes that still matter on the full-feature branch.
- [ ] **Systematically Repair Remaining Behavioral Drift**: Current likely remaining areas include deeper governance/federation/frontend-contract breakage beyond the now-fixed profile, bounty, and root-route issues.

## 🔴 Critical: Product Recovery
- [ ] **Preserve Full Feature Surface On Restore Branch**: Keep using the rewind branch as the recovery vehicle for removed systems instead of re-pruning them during conflict resolution.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live drift protections.

## 🟡 High: Verification
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Run Wider Restore-Branch Local Validation Now That Backend Deps Exist In The Worktree**
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Stabilized Restore-Branch Profile + Match Action Persistence**: Wrapped event append/publish failures in non-blocking safeguards so profile updates and match actions continue on the richer branch.
- [x] **Restored Missing Frontend Build Primitives On Restore Branch**: Added `components/ui/avatar.tsx`, `components/ui/progress.tsx`, and `components/ui/select.tsx`, and cleaned broken council/merchant/wasm files.
- [x] **Validated Key Restore-Branch Flows Locally**: `OnboardingEdgeCasesTest`, `OnboardingProfileUpdateTest`, `ProfileUpdateTest`, `PublicWebRoutesTest`, `MatchBountyTest`, and local frontend build all passed in the restore worktree.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
