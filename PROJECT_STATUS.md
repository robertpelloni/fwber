# Project Status — fwber v1.0.63 (Federation Follow Accept Handling)

**Date:** 2026-04-02  
**Version:** 1.0.63 "Federation Follow Accept Handling"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Federation Follow Accept Handling
- **Follow State Loop Completed Further**: `POST /api/federation/users/{id}/inbox` now processes inbound `Accept` activities for follow requests and promotes the matching local `Following` record from `pending` to `accepted`.
- **Accept Validation Tightened**: The inbox only applies follow acceptance when the `Accept` payload references the correct local actor URI and the remote actor matches the followed actor, preventing malformed accept payloads from mutating unrelated follow state.
- **Outbound Follow Identity Corrected**: Federated follow activities now identify the local actor with fwber's actual ActivityPub actor endpoint (`/api/federation/users/{id}`), aligning follow requests with the exposed actor document and the new inbox accept checks.
- **Regression Coverage Added**: Focused backend tests now prove inbox `Accept` activities correctly finalize pending follows without regressing follow, unfollow, actor, or outbox behavior.

## Federation Protocol Progress
- **Pending Followings Can Resolve**: The federation activity UI can now represent a more realistic follow lifecycle because the backend no longer leaves all remote follow requests stuck in `pending`.
- **Protocol Hardening Started**: This release improves protocol correctness without yet claiming full signed delivery or inbox signature verification; those remain the next hardening steps.
- **Existing UI Benefits Automatically**: The federation activity center, actor detail view, and following lists all benefit from the corrected backend status transitions without needing new page-level UI changes.

## ✅ Release Focus
- [x] Added inbox support for follow `Accept` activities and promoted matching local followings to `accepted`.
- [x] Corrected outbound federated follow payloads to use the live fwber actor endpoint.
- [x] Added focused backend coverage for the new accept-flow state transition while preserving the existing ActivityPub test baseline.
