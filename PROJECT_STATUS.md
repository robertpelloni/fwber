# Project Status — fwber v1.0.47 (Scene Discovery)

**Date:** 2026-04-02  
**Version:** 1.0.47 "Scene Discovery"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Scene Discovery
- **Scene-Aware Match Ranking Shipped**: Matches now blend followed-topic overlap and structured scene tags from journals, public groups, and interests into ranking and payload metadata.
- **Shared Scene Context in Swipe Cards**: The match deck now surfaces scene overlap headlines, topic chips, and shared scene tags alongside shared interests.
- **Profile Scene Summaries Added**: Public profiles now serialize followed topics, scene tags, and summary counts so scene identity shows up outside the match feed too.
- **Topic Graph Reuse Preserved**: This slice builds directly on the v1.0.46 topic-hub graph rather than introducing a second discovery taxonomy.

## ✅ Release Focus
- [x] Added scene-overlap scoring and serialization to the match engine using followed topics plus visible scene activity.
- [x] Added profile scene summaries to public/authenticated profile responses.
- [x] Added swipe-card and public-profile UI for scene discovery cues.
- [x] Added backend regression coverage and successful frontend lint/build/fresh-type-check validation for the scene-discovery slice.
