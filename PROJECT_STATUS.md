# Project Status — fwber v1.0.51 (Trust-Aware Local Pulse Ranking)

**Date:** 2026-04-02  
**Version:** 1.0.51 "Trust-Aware Local Pulse Ranking"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Trust-Aware Local Pulse Ranking
- **Trust-Aware Feed Ordering Shipped**: Local Pulse artifacts now rank by a privacy-safe composite of trusted connections, scene alignment, and freshness instead of pure recency.
- **Privacy Boundaries Preserved**: Friendship, confirmed relationship-link, and shared-circle checks stay internal to ranking; no private graph edges are serialized into the feed payload.
- **Shared Graph Reuse Preserved**: Local Pulse ranking now builds on the same topic/scene graph already driving matches, profiles, recommendations, and pulse-card scene cues.
- **Feed Explanation Added**: Local Pulse now exposes high-level ranking-strategy metadata so the UI can explain why the feed prioritizes trusted, scene-aligned, fresh activity.

## ✅ Release Focus
- [x] Added a batch trust-map service for Local Pulse ranking using friendships, confirmed relationship links, and shared active circles.
- [x] Reordered Local Pulse artifacts by trust + scene alignment + freshness without widening the payload's private social graph surface.
- [x] Added regression coverage for the new ordering behavior and updated the Local Pulse UI/types to explain the ranking strategy.
