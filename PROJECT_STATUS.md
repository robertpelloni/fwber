# Project Status — fwber v1.0.60 (Trust-Aware Deal Ranking)

**Date:** 2026-04-02  
**Version:** 1.0.60 "Trust-Aware Deal Ranking"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Trust-Aware Deal Ranking
- **Trust-Aware Deals Shipped**: Nearby deals now rank with a privacy-safe composite of trusted merchants, scene alignment, deal health, freshness, and distance instead of relying only on baseline sort choices.
- **Browse Contract Stabilized**: The promotions browse path now has a canonical `merchant()` relation alias on `Promotion`, and `GET /api/deals` returns paginated ranked payloads with `deals` and `meta.ranking_strategy` for consistent frontend consumption.
- **Privacy Boundaries Preserved**: Friendship, confirmed relationship-link, and shared-circle checks stay internal to ranking; the ranked deals API exposes only a high-level ranking strategy summary instead of private graph details.
- **Deals UI Explanation Added**: The deals page now explains why trusted, scene-aligned merchants surface first and shows merchant verification, scene cues, ranking score, and distance directly on each card.

## ✅ Release Focus
- [x] Extended nearby deal discovery with the same privacy-safe trust-aware composite already used by Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, and audio rooms.
- [x] Fixed the directly coupled `merchant` versus `merchantProfile` browse-path mismatch without widening the API's private graph surface.
- [x] Added ranked deal metadata/UI explanation and focused regression coverage while preserving pagination behavior.
