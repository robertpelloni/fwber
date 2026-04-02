# Project Status — fwber v1.0.58 (Trust-Aware Nearby User Ranking)

**Date:** 2026-04-02  
**Version:** 1.0.58 "Trust-Aware Nearby User Ranking"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Trust-Aware Nearby User Ranking
- **Trust-Aware Nearby People Shipped**: Nearby people discovery now ranks users with a privacy-safe composite of trusted connections, scene alignment, activity recency, and distance instead of pure radius ordering alone.
- **Privacy Boundaries Preserved**: Friendship, confirmed relationship-link, and shared-circle checks stay internal to ranking; the nearby users API exposes only a high-level ranking strategy summary instead of private graph details.
- **Shared Graph Reuse Preserved**: Nearby user ranking now reuses the same trust map and scene-signal architecture already powering matches, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, Local Pulse card cues, and Local Pulse ranking.
- **Nearby UI Explanation Added**: The nearby people page now explains why trusted, scene-aligned people surface first and shows scene cues directly on each result card.

## ✅ Release Focus
- [x] Extended nearby people discovery with the same privacy-safe trust-aware composite already used by Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, and venues.
- [x] Preserved a tight change boundary by ranking only the nearby people browse seam and keeping trust data internal to ordering.
- [x] Added nearby-user ranking metadata/UI explanation and focused regression coverage for the new ranked discovery contract.
