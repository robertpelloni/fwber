---
feature: interest-social-graph
doc_type: implementation
status: draft
owner: copilot
updated: 2026-04-02
---

# Feature Implementation Notes — interest-social-graph

## Initial Slice Recommendation
Implement **structured interest graph** first.

### Why this slice first
- It unlocks the data foundation required by the other requested capabilities.
- It can improve matching/discovery without forcing the full content/privacy stack immediately.
- It creates a clean path for topic hubs, journals, and scene discovery to share one taxonomy.

## Entry Points
- Backend profile/matching/discovery services.
- Frontend profile settings/edit surfaces.
- Matching/discovery UI filters and shared-interest rendering.

## Expected Backend Changes
- Normalize `UserProfile.interests` values on save.
- Extend `MatchFilterRequest`, `MatchController`, `MatchResource`, and `AIMatchingService` so `/api/matches` accepts interest filters and returns shared-interest metadata.
- Add tests for validation, filtering, scoring, and response shape integration.

## Expected Frontend Changes
- Reuse the existing interest editor UI instead of replacing it.
- Add shared-interest display in match cards/details.
- Add match-filter controls for interest-aware filtering.

## Rollout Notes
- Start with the current curated frontend interest list and normalize values server-side.
- Keep phase 1 read/write paths narrow and reversible.
- Avoid coupling the initial schema too tightly to one future content type.
