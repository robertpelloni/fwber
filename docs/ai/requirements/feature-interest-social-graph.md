---
feature: interest-social-graph
doc_type: requirements
status: draft
owner: copilot
updated: 2026-04-02
---

# Feature Requirements — interest-social-graph

## Problem Statement
fwber already supports proximity, matching, groups, Local Pulse, and merchant/community surfaces, but the social graph is still too shallow for users who want richer identity and discovery. The current product can tell who is nearby and what broad product surfaces exist, but it cannot yet model durable interests, longer-form self-expression, relationship context, or privacy-scoped social circles well enough to support intentional community building.

The requested feature set closes that gap by introducing a structured interest graph, journals/field notes, topic hubs tied to Local Pulse and groups, relationship/status links, friends-only or circle-only visibility controls, and scene-based local discovery around interests rather than raw proximity alone.

## Users
- Primary: regular fwber users who want more intentional discovery than swipe/proximity-only matching.
- Secondary: existing matches, friends, and circles who need clearer privacy boundaries and relationship context.
- Tertiary: community-oriented users who want topic-centered local discovery, journaling, and scene participation.

## Goals
1. Let users represent interests as structured, queryable graph data instead of only freeform profile copy.
2. Support long-form journals/field notes that deepen identity and create richer discovery input.
3. Connect topic hubs to Local Pulse and groups so interest-based discovery can become local and social.
4. Add explicit relationship/status links between users with user-controlled visibility.
5. Add visibility controls that support friends-only and circle-only access for relevant social content.
6. Enable scene-based discovery that blends interests, local context, and social graph signals.

## Non-Goals
1. Replacing the entire existing matching engine in one release.
2. Shipping a fully federated cross-server interest graph in phase 1.
3. Building a brand-new moderation stack from scratch for long-form content.
4. Creating a new standalone social feed unrelated to Local Pulse, groups, and existing profile surfaces.

## Requested User Stories
1. As a user, I can select and organize structured interests so my profile and matching signals reflect who I actually am.
2. As a user, I can publish journals or field notes that express more depth than a short bio.
3. As a user, I can discover local topic hubs tied to my interests and nearby scenes.
4. As a user, I can mark certain content as friends-only or circle-only so not everything is public.
5. As a user, I can define relationship/status links with another user so our context is clear and consensual.
6. As a user, I can discover people, groups, and local activity based on shared scenes and interests, not just distance.

## Success Criteria
1. Users can create, edit, and store structured interests with normalized taxonomy and optional strength/priority metadata.
2. At least one discovery surface can rank or filter by shared structured interests and local scene relevance.
3. Users can create and view long-form journal entries with working privacy controls.
4. Topic hubs can aggregate relevant users, journals/posts, and nearby activity through existing Local Pulse/groups concepts.
5. Relationship/status links are modeled explicitly and only shown according to visibility/privacy rules.
6. Privacy settings prevent friends-only and circle-only content from leaking into broader discovery surfaces.

## Constraints
- Must fit the existing Laravel + Next.js stack and current auth/privacy architecture.
- Must respect the project's privacy-first positioning and avoid exposing exact sensitive graph data publicly.
- Should reuse Local Pulse, groups, and matching infrastructure where practical instead of duplicating it.
- Must stay compatible with existing profile, group, and recommendation patterns.

## Open Questions
1. Should the initial interest taxonomy be fully curated, partially user-extensible, or hybrid?
2. What is the MVP definition of a "circle" in this codebase: followers, friends, groups, or a new entity?
3. Should journals live on the profile, in Local Pulse, or in a separate user-writing surface that can also feed hubs?
4. Which relationship/status types are in scope for MVP versus later expansion?
5. How much of scene-based discovery should affect matching immediately versus launch first as browse/filter UX?

## Proposed Phase Ordering
1. Structured interest graph for profiles and matching.
2. Visibility primitives for friends-only and circle-only access.
3. Long-form journals / field notes.
4. Topic hubs tied to Local Pulse and groups.
5. Relationship/status links.
6. Scene-based local discovery ranking and browse surfaces.
