# Project Status — fwber v1.0.57 (Trust-Aware Nearby Venue Ranking)

**Date:** 2026-04-02  
**Version:** 1.0.57 "Trust-Aware Nearby Venue Ranking"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Trust-Aware Nearby Venue Ranking
- **Trust-Aware Venues Shipped**: Nearby venue discovery now ranks venues with a privacy-safe composite of trusted recent visitors, scene alignment, venue health, freshness, and distance instead of pure distance ordering alone.
- **Privacy Boundaries Preserved**: Friendship, confirmed relationship-link, and shared-circle checks stay internal to ranking; the venues API exposes only a high-level ranking strategy summary instead of private graph details.
- **Shared Graph Reuse Preserved**: Venue ranking now reuses the same trust map and scene-signal architecture already powering matches, recommendations, nearby chatrooms, events, bulletin boards, group matching, Local Pulse card cues, and Local Pulse ranking.
- **Venues UI Explanation Added**: The venues page now explains why trusted, scene-aligned nearby venues surface first and shows venue health plus scene-aligned cues.

## ✅ Release Focus
- [x] Extended nearby venue discovery with the same privacy-safe trust-aware composite already used by Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, and group matching.
- [x] Preserved a tight change boundary by ranking only the nearby venues browse seam and keeping trust data internal to ordering.
- [x] Added venue ranking-strategy metadata/UI explanation and focused regression coverage for the new ranked discovery contract.
