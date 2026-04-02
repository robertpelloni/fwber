# Project Status — fwber v1.0.61 (Trust-Aware Chatroom Ranking & Sidebar Shell Sweep)

**Date:** 2026-04-02  
**Version:** 1.0.61 "Trust-Aware Chatroom Ranking & Sidebar Shell Sweep"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Trust-Aware Chatroom Ranking
- **Trust-Aware Chatrooms Shipped**: The main chatroom browse feed now ranks rooms with a privacy-safe composite of trusted creators, scene alignment, community health, and freshness instead of relying only on raw activity sort options.
- **Browse Contract Stabilized**: `GET /api/chatrooms` now returns high-level `meta.ranking_strategy` metadata plus ranked room cues that the main chatroom directory can render consistently without exposing private graph details.
- **Privacy Boundaries Preserved**: Friendship, confirmed relationship-link, and shared-circle checks remain internal scoring inputs only; the chatroom payload exposes only high-level strategy explanations and room-facing scene cues.
- **Regression Coverage Added**: Focused backend tests now prove chatroom browse results expose ranking metadata and that a trusted, scene-aligned room can outrank a busier generic room.

## Shared Sidebar Shell Sweep
- **Requested Pages Unified**: Groups, events, proximity chatrooms, conference pulse, date planner, audio rooms, burner bridge, bulletin boards, nearby, leaderboard, and federation now render inside the same `AppHeader` desktop shell with the left sidebar.
- **Federation Entry Promoted**: `/federation` is now the primary navigation route while the existing settings federation experience remains intact behind that entrypoint.
- **Shell Pattern Clarified**: The durable sidebar fix is using `AppHeader` and the shared app-shell classes, not adding ad hoc page-local sidebars.

## ✅ Release Focus
- [x] Extended chatroom browse discovery with the same privacy-safe trust-aware composite already used across the rest of the shipped discovery stack.
- [x] Added ranked chatroom metadata/UI explanation and focused regression coverage while preserving privacy boundaries.
- [x] Finished the requested shared-sidebar shell rollout across the remaining community and discovery pages, including the promoted federation route.
