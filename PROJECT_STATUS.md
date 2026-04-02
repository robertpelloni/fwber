# Project Status — fwber v1.0.46 (Topic Hubs)

**Date:** 2026-04-02  
**Version:** 1.0.46 "Topic Hubs"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Topic Hubs
- **Canonical Topic Catalog Shipped**: Structured topic records now anchor scene discovery across interests, groups, journals, and Local Pulse.
- **Followable Social Scenes**: Authenticated users can follow and unfollow topics, and fetch a followed-topics list for future personalization.
- **Hub Detail Aggregation**: Topic detail now combines public groups, visibility-safe journals, and topic-scoped Local Pulse artifacts in one place.
- **Pulse Topic Filtering Preserved**: Local Pulse posting and feed queries accept an optional topic slug so scene discovery extends beyond raw distance.

## ✅ Release Focus
- [x] Added topic catalog/follow storage, topic browse/detail APIs, and topic-aware Local Pulse backend wiring.
- [x] Added `/topics` browse and `/topics/[slug]` hub detail pages with reusable topic hooks and cards.
- [x] Added backend regression coverage plus successful frontend lint/build/fresh-type-check validation for the topic-hub slice.
