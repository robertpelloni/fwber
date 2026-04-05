# TODO — fwber Immediate Action Items

> **Version:** 1.9.8
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Rewind Branch Execution
- [ ] **Inspect Latest Restore-Branch Backend CI On `35bdf54f5`**: Confirm whether the direct messaging/WebFinger stabilization fixes reduce the remaining backend failures and isolate the next blockers.
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Next target commits should still include executable-bit tracking and roast-preview smoke hardening, then any remaining runtime fixes that materially help the full-feature branch.
- [ ] **Attack Remaining Behavioral Drift In Priority Order**: After profile, messaging, and webfinger fixes, likely next areas are governance/federation edges, caching expectations, and any remaining old-contract API mismatches surfaced by CI.

## 🔴 Critical: Product Recovery
- [ ] **Preserve Full Feature Surface On Restore Branch**: Keep using the rewind branch as the recovery vehicle for removed systems instead of re-pruning them during conflict resolution.
- [ ] **Keep Hetzner As The Non-Negotiable Production Baseline**: Do not regress GitHub Hetzner deployment, nginx/systemd templates, smoke checks, deploy verification, or live drift protections.

## 🟡 High: Verification
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Run Wider Restore-Branch Local Validation Now That Composer + NPM Dependencies Exist In The Worktree**
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Stabilized Restore-Branch Messaging + WebFinger Contracts**: Added direct restore-branch fixes for local/federated receiver validation, non-blocking message event append, and federated-only WebFinger actor URI behavior.
- [x] **Stabilized Restore-Branch Profile + Match Action Persistence**: Wrapped event append/publish failures in non-blocking safeguards so profile updates and match actions continue on the richer branch.
- [x] **Validated Key Restore-Branch Flows Locally**: `DirectMessageTest`, `ActivityPubTest`, `ActivityPubOutboundTest`, `OnboardingEdgeCasesTest`, `OnboardingProfileUpdateTest`, `ProfileUpdateTest`, `PublicWebRoutesTest`, `MatchBountyTest`, and local frontend build all passed in the restore worktree.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
