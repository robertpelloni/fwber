# Project Status — fwber v1.0.62 (Federation Outbox Visibility)

**Date:** 2026-04-02  
**Version:** 1.0.62 "Federation Outbox Visibility"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Federation Outbox Visibility
- **Real Public Outbox Shipped**: `GET /api/federation/users/{id}/outbox` now returns a real ActivityStreams `OrderedCollection`, with `?page=true` exposing a concrete `OrderedCollectionPage` of public `Create` activities for federated users.
- **Local Board Posts Federated**: Active public `board_post` proximity artifacts are now mapped into ActivityPub `Create` activities containing `Note` objects, giving fwber a meaningful public outbox without taking on full signed remote delivery in the same slice.
- **Filtering Contract Stabilized**: The outbox excludes expired artifacts and unrelated types so only active board-post content becomes part of the exposed public ActivityPub surface.
- **Regression Coverage Added**: Focused backend tests now prove the outbox page includes only active board posts and preserves the expected ActivityStreams contract.

## Federation UI Expansion
- **Dedicated Outbox Page Added**: `/settings/federation/outbox` now lets signed-in users inspect the public activities exposed for their federated identity, including activity IDs, note bodies, timestamps, and audience.
- **Activity Center Enriched**: The federation activity center now merges the user’s own outbox entries into the activity timeline and adds an outbox summary card with a direct deep link.
- **Hub Navigation Completed Further**: The main federation hub now links directly to the outbox alongside the activity center and global feed, building on the previously promoted `/federation` entry route.

## ✅ Release Focus
- [x] Replaced the placeholder ActivityPub outbox with a real public ActivityStreams collection backed by active local board posts.
- [x] Added focused backend coverage for the new outbox page behavior and filtering rules.
- [x] Expanded the federation frontend with typed outbox helpers, a dedicated outbox page, and activity-center integration.
