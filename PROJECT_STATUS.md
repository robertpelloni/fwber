# PROJECT_STATUS.md - fwber v1.7.0 (Rewind Navigation Recovery + Missing Activity Surfaces)

**Date:** 2026-04-05
**Version:** 1.7.0 "Rewind Navigation Recovery + Missing Activity Surfaces"
**Status:** ✅ **RESTORE BRANCH NOW SURFACES APPROVED FEATURES IN THE MAIN SHELL; ACTIVITY + NOTIFICATIONS DESTINATIONS RECOVERED**

---

## 🎯 What This Release Delivered
This release focused on the user-facing symptom behind the earlier restore complaint: the rewind branch already contained many recovered systems, but the signed-in shell still emphasized excluded areas and still lacked obvious destinations for restored activity/notification flows.

Delivered:
- the restore-branch app header and left rail now prioritize approved restored surfaces such as friends, activity, events, wallet, roast, merchant, moderation, notifications, and travel mode
- removed excluded federation / journal / governance-era emphasis from the primary shell navigation without deleting the underlying compatibility work yet
- added a real top-level `/activity` page backed by dashboard activity data
- added a real top-level `/notifications` inbox page with mark-as-read and mark-all-read behavior
- added shared notification route helpers in `fwber-frontend/lib/notifications.ts`
- rebuilt the rewind dashboard so restored surfaces are visible and reachable instead of buried in legacy/excluded cards

## ✅ Why This Matters
A branch is not meaningfully “restored” if users still cannot reach the restored areas from the main app shell. This release closes that UX gap while still respecting the approved exclusion list.
