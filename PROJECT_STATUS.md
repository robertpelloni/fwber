# PROJECT_STATUS.md - fwber v1.3.0 (Trust & Safety Hardening)

**Date:** 2026-04-04
**Version:** 1.3.0 "Trust & Safety Hardening"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## 🎯 What This Release Solved
This release focused on a core product truth: in a privacy-first proximity dating app, **blocking must be absolute**. It cannot be cosmetic UI state. It must sever discovery, sever messaging, sever match visibility, and remain consistent across backend and frontend contracts.

## 🛡️ Trust & Safety Hardening
- **Block relationships are now enforced everywhere that matters:**
  - discovery feed candidates
  - established match/conversation lists
  - direct match actions (`like`, `pass`, `super_like`)
  - messaging attempts between blocked users
- **Blocking now actively severs pre-existing connections:** when a user blocks someone, the backend deactivates any existing `user_matches` row and deletes both sides of historical `match_actions` so the blocked user cannot re-enter the queue through stale mutual-like state.
- **Cache invalidation is now symmetrical:** match-feed and established-match caches are flushed for both users on block and unblock so stale matches do not linger after a safety action.

## 🔧 Hidden Bugs Uncovered and Fixed
- **Frontend/backend payload mismatch:** the web safety client was posting `blocked_id`, but the backend validator required `user_id`. This meant the block flow looked implemented yet could fail at runtime. The client contract is now aligned.
- **Missing unblock route:** the backend controller supported unblock, but the API route was never registered. `DELETE /api/blocks/{userId}` is now live.
- **Discovery engine still depended on archived systems:** the matching service was still eager-loading removed `followedTopics` relations and querying the old `date_feedback` table. Those legacy assumptions caused fresh-install and SQLite test crashes. The active matching path now uses simplified core-safe logic and gracefully skips archived-table modifiers.

## ✅ Validation
- **Backend regression coverage added:** `tests/Feature/BlockSafetyFlowTest.php`
- **Verified passing locally:**
  - `php artisan test tests/Feature/BlockSafetyFlowTest.php tests/Feature/CoreDatingFlowTest.php`
  - Result: **22 passed**

## ✅ Release Focus
- [x] Enforce block state across discovery, established matches, and messaging.
- [x] Fix frontend safety API request payload contract.
- [x] Expose unblock route in the active API surface.
- [x] Remove archived social-graph assumptions from the core discovery path.
- [x] Add regression coverage proving safety actions actually sever access.
