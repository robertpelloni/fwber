# Project Status — fwber v1.0.54 (Trust-Aware Event Ranking)

**Date:** 2026-04-02  
**Version:** 1.0.54 "Trust-Aware Event Ranking"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Trust-Aware Event Ranking
- **Trust-Aware Events Shipped**: Nearby event discovery now ranks events with a privacy-safe composite of trusted organizers, scene alignment, freshness, and distance instead of raw distance or start-time ordering alone.
- **Privacy Boundaries Preserved**: Friendship, confirmed relationship-link, and shared-circle checks stay internal to ranking; the events API exposes only a high-level ranking strategy summary instead of private graph details.
- **Shared Graph Reuse Preserved**: Event ranking now reuses the same trust map and scene-signal architecture already powering matches, recommendations, nearby chatrooms, Local Pulse card cues, and Local Pulse ranking.
- **Events UI Explanation Added**: The events page now explains why trusted, scene-aligned nearby events surface first and shows scene-aligned event cues on cards.

## ✅ Release Focus
- [x] Extended nearby event discovery with the same privacy-safe trust-aware composite already used by Local Pulse, recommendations, and nearby chatrooms.
- [x] Preserved a tight change boundary by ranking only the nearby events browse seam and keeping trust data internal to ordering.
- [x] Added event ranking-strategy metadata/UI explanation and focused regression coverage for the new ranked discovery contract.
