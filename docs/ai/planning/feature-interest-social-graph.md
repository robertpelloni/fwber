---
feature: interest-social-graph
doc_type: planning
status: draft
owner: copilot
updated: 2026-04-02
---

# Feature Planning — interest-social-graph

## Implementation Strategy
Deliver this initiative as phased slices that each create end-user value while preserving compatibility with existing profile, matching, and community systems.

## Task Breakdown
### Phase 1 — Structured interest graph
1. Normalize the existing `UserProfile.interests` write path so duplicate/case-variant tags collapse to canonical values.
2. Keep the current profile/settings API contract for reading and updating user interests.
3. Add shared-interest ranking/filter support to `/api/matches`.
4. Add frontend match-filter controls and match-card shared-interest rendering.
5. Add backend/frontend tests for interest-aware matching behavior.
6. Defer taxonomy tables and user-interest edge entities until phase 1 usage proves the first curated interest set.

### Phase 2 — Privacy primitives
1. Standardize visibility enums/rules for profile-linked social content.
2. Introduce server-side policy enforcement for friends-only and circle-only access.
3. Add reusable frontend visibility controls.

### Phase 3 — Journals / field notes
1. Create journal schema and API.
2. Add authoring UI and profile-linked rendering.
3. Add moderation/privacy enforcement and tests.

### Phase 4 — Topic hubs
1. Create topic hub aggregation service.
2. Connect hubs to interests, groups, Local Pulse, and journals.
3. Add hub browse/detail UI.

### Phase 5 — Relationship/status links
1. Create relationship link schema and policy.
2. Add create/confirm/update UI.
3. Expose visibility-aware relationship context on profile surfaces.

### Phase 6 — Scene-based discovery
1. Extend discovery ranking with interest plus local-scene signals.
2. Add scene browse/filter UI.
3. Measure ranking quality and privacy behavior.

## Dependencies
- Privacy primitives should land before journals, relationship links, and full hub visibility.
- Structured interests should land before topic hubs and scene discovery.
- Topic hubs depend on interest taxonomy and at least one content source.

## Risks
1. Scope explosion if all six requested capabilities are attempted in one release.
2. Privacy leaks if visibility enforcement is not centralized early.
3. Taxonomy churn if interest structure is overdesigned before live use.
4. Discovery complexity if scene-based ranking is coupled too tightly to raw geo logic too soon.

## Recommended Implementation Order
1. Structured interest graph.
2. Visibility primitives.
3. Journals.
4. Topic hubs.
5. Relationship/status links.
6. Scene-based local discovery.

## Definition of Ready for Implementation
- Requirements, design, and planning docs accepted.
- Initial interest normalization strategy chosen.
- Circle/friends visibility semantics clarified enough for phase 2.
- First implementation slice explicitly scoped to structured interests for matching only.

## PR Description Draft
### Summary
Scaffold documentation for the `interest-social-graph` initiative covering structured interests, journals, topic hubs, relationship links, privacy-scoped visibility, and scene-based discovery.

### Requirements Doc
`docs/ai/requirements/feature-interest-social-graph.md`

### Key Changes
- Added requirements, design, planning, implementation, and testing docs for the umbrella feature.
- Sequenced the work into phased slices with structured interest graph as phase 1.
- Captured privacy, discovery, and reuse constraints tied to Local Pulse, groups, and existing profile systems.

### Test Status
- Documentation-only planning slice.

### Readiness Checklist
- [ ] Requirements reviewed
- [ ] Design reviewed
- [ ] Phase 1 scope approved
- [ ] Execution plan selected
