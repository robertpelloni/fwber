---
feature: interest-social-graph
doc_type: design
status: draft
owner: copilot
updated: 2026-04-02
---

# Feature Design — interest-social-graph

## Overview
The feature should be built as an extension of the existing social graph, profile, Local Pulse, groups, and discovery systems rather than a parallel subsystem. Phase 1 intentionally stays narrow: normalize the existing `UserProfile.interests` array, expose shared-interest metadata in the match feed, and allow `/api/matches` filtering/scoring to use those interests immediately. The later umbrella phases can still graduate to taxonomy tables and richer graph entities, but the first shipped slice should reuse the current profile storage seam instead of introducing a second profile-interest system.

## Architecture Overview
```mermaid
flowchart LR
    A[Profile editor UI] --> B[PUT /api/profile]
    B --> C[ProfileController]
    C --> D[UserProfile.interests]
    D --> E[AIMatchingService]
    F[Match filter UI] --> G[GET /api/matches?interests[]=...]
    G --> H[MatchController]
    H --> E
    E --> I[shared_interests + score]
    I --> J[MatchResource]
    J --> K[Matches page cards]
```

## Architecture Changes
### Backend
- Phase 1: keep `user_profiles.interests` as the source of truth, but normalize values on write so matching and filtering see canonical lowercase tags.
- Phase 2+: add normalized interest taxonomy tables and user-interest edge tables when curation, strength, visibility, and hub linking need first-class persistence.
- Add journal/field-note entities with visibility controls and optional topic linkage.
- Add relationship/status link entities with mutual-consent or directed-link semantics depending on type.
- Extend discovery services to accept structured interest and scene signals.
- Extend Local Pulse/group aggregation paths so topic hubs can reuse existing community activity primitives.

### Frontend
- Keep the current interest picker/editor in profile/settings flows, but make it feed phase-1 structured match behavior.
- Add journal composition and reading surfaces.
- Add topic hub pages/cards/components that can show local activity, people, and linked content.
- Add relationship/status management UI where appropriate in profile/settings or match/friend context.
- Add privacy controls to relevant create/edit flows and discovery filtering surfaces.

## Data Model
### Core entities
1. `user_profiles.interests` (phase 1)
   - existing JSON array
   - normalized lowercase strings
   - reused by profile serialization and match/discovery logic immediately

2. `interests` (phase 2+)
   - `id`
   - `slug`
   - `label`
   - `category`
   - `description`
   - `is_active`

3. `user_interests`
   - `id`
   - `user_id`
   - `interest_id`
   - `strength` or `weight`
   - `visibility`
   - `source` (`manual`, `inferred`, `journal`, etc.)

4. `journals`
   - `id`
   - `user_id`
   - `title`
   - `body`
   - `visibility`
   - `published_at`
   - `metadata`

5. `journal_interest_links`
   - `journal_id`
   - `interest_id`

6. `relationship_links`
   - `id`
   - `source_user_id`
   - `target_user_id`
   - `relationship_type`
   - `status`
   - `visibility`
   - `confirmed_at`

7. `circles` and `circle_memberships` (if existing friends/groups semantics are not enough)
   - only introduced if reuse of current social entities is not sufficient.

### Topic hub aggregation model
Topic hubs do not need a full new persistence model in phase 1 if they can be derived from:
- interest taxonomy
- linked journals
- linked groups
- linked Local Pulse artifacts/posts
- nearby events and social scenes

## API / Interface Changes
### Initial API surface
1. Phase 1: `PUT /api/profile` continues to accept `interests: string[]`
2. Phase 1: `GET /api/profile` and `GET /api/users/{id}` continue exposing `interests`
3. Phase 1: `GET /api/matches?interests[]=travel&interests[]=music`
4. Phase 1: `GET /api/matches` returns `shared_interests` and `shared_interest_count`
5. Phase 2+: `GET /api/interests`
6. Phase 3+: `GET /api/journals`
7. Phase 3+: `POST /api/journals`
8. Phase 4+: `GET /api/topic-hubs/{slug}`
9. Phase 5+: `POST /api/relationship-links`
10. visibility-aware retrieval on profile/journal/hub endpoints

### Discovery contract evolution
Existing discovery endpoints should eventually accept:
- interest filters
- hub/topic filters
- privacy-aware graph filters
- scene relevance mode

## Components
### Phase 1 components
- Interest picker/editor
- Interest chips and graph summary cards
- Shared-interest comparison UI on profiles/matches
- Matching/discovery filters by structured interest

### Later components
- Journal editor/viewer
- Topic hub overview page
- Relationship status card
- Scene discovery explorer

## Privacy and Security
- Interest visibility must support at least: public, matches/friends, circle-only, private.
- Journals must enforce visibility server-side, not only in UI.
- Relationship/status links should require explicit confirmation for mutual states where needed.
- Scene discovery should use fuzzy/localized signals rather than exposing exact social graph membership broadly.

## Moderation
- Journals and hubs should reuse existing moderation patterns for user-generated content.
- Long-form content should respect rate limits, abuse controls, and moderation status fields if needed.

## Performance Considerations
- Phase-1 interest overlap should stay lightweight and reuse the cached `/api/matches` feed path.
- Interest lookups should become index-friendly and cached where practical once normalized taxonomy tables land.
- Topic hubs should aggregate from pre-filtered or paginated sources rather than loading all attached content at once.
- Scene discovery should leverage existing geo/discovery infrastructure and eventually `fwber-geo` for dense ranking.

## Design Decisions
1. Phase 1 reuses `UserProfile.interests` to deliver user-visible matching improvements immediately, then graduates to taxonomy tables later.
2. Use a normalized interest taxonomy instead of pure freeform tags once hubs, journals, and visibility rules need richer semantics.
3. Keep topic hubs as an aggregation layer first, not a fully separate social network surface.
4. Reuse existing Local Pulse, groups, and profile primitives wherever possible.
5. Sequence privacy primitives early because they affect journals, relationships, and discovery correctness.

## Current Gaps To Resolve In Follow-Up
- The original draft implied a brand-new graph persistence layer in phase 1, but the live codebase already has `UserProfile.interests`, `ProfileController`, `UserProfileResource`, `MatchController`, and `AIMatchingService` seams that should be reused first.
- The design still leaves the eventual curated taxonomy model open; that remains acceptable for now because the current release slice is the shared-interest matching layer, not the full taxonomy rollout.
- Journal, topic hub, relationship-link, and circle diagrams still need their own follow-up design detail once those phases move from umbrella planning into implementation.
