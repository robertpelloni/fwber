---
feature: interest-social-graph
doc_type: testing
status: draft
owner: copilot
updated: 2026-04-02
---

# Feature Testing Strategy — interest-social-graph

## Phase 1 Test Focus
### Backend
- Interest normalization on profile write.
- Validation for malformed match-interest filter payloads.
- Shared-interest filtering and response shape on `/api/matches`.
- Shared-interest scoring behavior when profiles overlap.

### Frontend
- Existing interest picker still renders and writes the same values.
- Match filter UX can apply shared-interest filters.
- Match cards render shared-interest chips and counts.
- Type coverage for new match payload fields.

## Later-Phase Test Focus
- Journal privacy enforcement.
- Topic hub aggregation correctness.
- Relationship link consent/visibility flows.
- Scene discovery ranking behavior under mixed geo and interest signals.

## Regression Priorities
1. Privacy leakage across friends-only and circle-only content.
2. Discovery regressions on existing nearby/matching flows.
3. Taxonomy drift causing mismatched frontend/backend interest identifiers.
