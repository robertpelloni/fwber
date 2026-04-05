# TODO — fwber Immediate Action Items

> **Version:** 1.9.10
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Live Runtime Hardening
- [ ] **Verify Dashboard Storage Error Recovery In Production UI**: Confirm the reported `Access to storage is not allowed from this context` errors disappear after the frontend deploy reaches users.
- [ ] **Confirm E2E Restore Probe Noise Is Gone**: Re-check live dashboard/security screens and verify blocked-storage contexts no longer spam restore-route requests.
- [ ] **Continue Production 500 / Runtime Sweep**: Keep fixing real browser/runtime failures before declaring the live shell stable.

## 🔴 Critical: Rewind Branch Execution
- [ ] **Inspect Latest Restore-Branch Backend CI**: Confirm the restore-branch compatibility tranches are reducing remaining failures and isolate the next blockers.
- [ ] **Continue Mandatory Hetzner Replay On Restore Branch**: Keep replaying or directly patching the next runtime-hardening tranche that materially helps the full-feature branch.
- [ ] **Attack Remaining Behavioral Drift In Priority Order**: After profile, messaging, webfinger, caching, and storage fixes, continue isolating the next concrete old-contract mismatches surfaced by CI.

## 🟡 High: Verification
- [ ] **Compare Restore Branch Against Main After Each Replay Tranche**
- [ ] **Run Wider Restore-Branch Local Validation Now That Composer + NPM Dependencies Exist In The Worktree**
- [ ] **Production 500 Error Sweep On Restore Branch Once It Boots**

## ✅ Recently Completed
- [x] **Extended Browser Storage Guard Sweep**: Hardened additional storage-dependent frontend modules (offline store, preview telemetry, photos, verification, recommendation/content-generation cache helpers, message storage, vault storage) and kept the production frontend build green.
- [x] **Dashboard Storage Guard + E2E Restore Probe Hardening**: Wrapped auth/realtime/browser storage access, hardened IndexedDB E2E key storage failure handling, and verified the live `security/keys/restore` route exists publicly on Hetzner with a `401` response.
- [x] **Stabilized Restore-Branch Messaging + WebFinger Contracts**: Added direct restore-branch fixes for local/federated receiver validation, non-blocking message event append, and federated-only WebFinger actor URI behavior.
- [x] **Stabilized Restore-Branch Profile + Match Action Persistence**: Wrapped event append/publish failures in non-blocking safeguards so profile updates and match actions continue on the richer branch.
- [x] **Validated Key Restore-Branch Flows Locally**: `DirectMessageTest`, `ActivityPubTest`, `ActivityPubOutboundTest`, `OnboardingEdgeCasesTest`, `OnboardingProfileUpdateTest`, `ProfileUpdateTest`, `PublicWebRoutesTest`, `MatchBountyTest`, and local frontend build all passed in the restore worktree.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
