# MVP Scope & Strategic Fusion Summary

## Vision
Hybrid platform uniting precise mutual-match hookups (shared wants + proximity + fairness) with a clean, trustworthy hyperlocal communication layer (ephemeral proximity posts / billboards). No dominant incumbent exists in proximity chat; fusing both increases retention and network effects while mitigating spam/catfish/clique risks via generated avatars, anti-abuse tooling, and fairness algorithms.

## Core Principles
1. Mutual Consent: Both sides must explicitly want each other's attributes (gender, orientation, relationship style).
2. Proximity First: Geospatial filtering with fuzzed served coordinates (privacy-safe) drives local relevance.
3. Fair Exposure: Prevent feed domination by power users; rotate and cap exposures.
4. Anti-Abuse by Default: Generated avatars only (MVP), rejection of links/phones/emails in bios & proximity content, rate limiting, content moderation hooks.
5. Open Source & Mostly Free: Transparent codebase, ethical monetization deferred.
6. Low Friction Onboarding: Minimal required profile fields; avatar auto-generation.
7. Fusion Layer: "Local Pulse" feed merges ephemeral proximity artifacts (chat snippets, board posts, room/announce) with potential matches for serendipitous discovery.

## Implemented Foundations (Current)
- Auth & Profiles with preferences.
- Matching engine (distance, age, mutual gender prefs, compatibility scoring).
- Messaging post-match with moderation pipeline.
- Location APIs + privacy update capability.
- Bulletin boards & chatroom prototypes.
- Telemetry, rate limiting, content moderation checks.
- Feature flags, email notifications, generated avatar request flow.
- Audit & real-time event scaffolding for moderation.

## MVP Inclusions (✅ = Implemented)
- ✅ Generated avatars mandatory; disable user photo uploads (AVATAR_MODE config flag).
- ✅ Required profile fields: age, gender, wants/preferences, location.
- ✅ Matching result list (20 candidates) with fairness adjustments.
- ✅ Local Pulse feed: merged proximity artifacts + candidate previews (`GET /proximity/local-pulse`).
- ✅ Proximity artifacts unified table with TTL (ephemeral chat ~45m, board post ~48h, announce ~2h).
- ✅ Artifact sanitizer (no URL/email/phone). Fuzz location before client serialization.
- ✅ Flagging & moderation_status escalation (3 flags → flagged status).
- ✅ Basic saturation penalty in compatibility ranking for overactive posters (-1 per 10 posts/24h, max -5).
- ✅ One-to-one chat creation on mutual match with system intro message.
- ⏳ OSS docs: LICENSE, CONTRIBUTING, PRIVACY, TERMS, README quickstart (pending).

## Non-MVP (Deferred)
- Public groups & large-scale chatrooms.
- Reactions, threads, presence indicators.
- Advanced AI recommendation ranking.
- Premium highlighting / sponsorship.
- Photo verification & optional real pictures.
- Geo-spoof anomaly detection heuristics (Phase 2).

## Data Model (Proximity Artifacts)
`proximity_artifacts`:
- id (PK)
- user_id (nullable for system tips)
- type (enum: chat, board_post, announce)
- content (text sanitized)
- location_lat, location_lng (raw internal)
- visibility_radius_m (int default 1000)
- moderation_status (clean|flagged|shadow_throttled|removed)
- expires_at (datetime)
- meta (json) for future extension (reactions, pinned)
- created_at, updated_at

Indexes: (location_lat, location_lng, type, expires_at), (user_id, created_at)

## Endpoints (Phase 1 Build - ✅ All Implemented)
- ✅ GET /proximity/feed?lat&lng&radius (all artifacts within radius)
- ✅ GET /proximity/local-pulse?lat&lng&radius (merged: artifacts + match candidates)
- ✅ POST /proximity/artifacts {type, content, lat, lng, radius?}
- ✅ GET /proximity/artifacts/{id}
- ✅ POST /proximity/artifacts/{id}/flag
- ✅ DELETE /proximity/artifacts/{id}

## Safety & Fairness Rules
- Sanitizer rejects URLs (http/https/www), emails, phone patterns.
- Daily post caps per user per type; beyond cap => soft saturation penalty.
- Fuzz served coordinates (random jitter within small radius) to mitigate precise tracking.
- TTL pruning scheduled every 5 minutes.
- Shadow throttling (Phase 2) placeholder: moderation_status influences feed distribution weighting.

## Matching Adjustments
- Inject saturation penalty: heavy proximity posting reduces compatibility score portion (freshness segment or an added penalty component).
- Maintain deterministic ordering after scoring.

## Phase Roadmap
Phase 1 (MVP Fusion): table + endpoints + sanitizer + TTL + feed merge + basic fairness + docs.
Phase 2 (Hardening): shadow throttling, geo-spoof detection, real-time unified stream.
Phase 3 (Growth): reactions, threads, seeded system tips, highlight experiments.
Phase 4 (Evolution): multi-region scaling, adaptive radius suggestions, semantic clustering.

## Testing Strategy (✅ Implemented)
- ✅ Unit: Sanitizer patterns, TTL computation, score penalty logic.
- ✅ Feature tests (17 tests, 60 assertions):
  - ProximityArtifactTest: CRUD, sanitizer, daily caps, flag escalation, removal
  - LocalPulseTest: merged feed, radius filtering, gender preferences, limits, compatibility indicators
  - AvatarModeTest: photo upload blocking, generated-only enforcement
- ✅ Integration: Full suite (131 passed) confirms no regressions in matching, messaging, groups.

## Open Source Posture
Add .env.example with safe defaults; clearly document how to disable/enable proximity layer.
Expose extension points (service class, meta JSON) for community contributions.

---

## Implementation Status (Jan 12, 2025)

### Phase 1 (MVP Fusion) - ✅ COMPLETE
- [x] Avatar-only enforcement (AVATAR_MODE config)
- [x] Proximity artifacts unified table (ProximityArtifact model)
- [x] Content sanitizer (blocks URLs/emails/phones)
- [x] TTL expiry (chat: 45min, board_post: 48h, announce: 2h)
- [x] Daily caps enforcement (chat: 30, board_post: 10, announce: 15)
- [x] Location fuzzing (±0.0008° deterministic jitter)
- [x] Flag escalation (3 flags → flagged status)
- [x] Saturation penalty in match scoring
- [x] Local Pulse merged endpoint
- [x] Comprehensive test coverage (131 tests passing)
- [x] Prune command (proximity:prune)

### Next Steps (Phase 2)
- [ ] Shadow throttling (reduce visibility for flagged users)
- [ ] Geo-spoof detection heuristics
- [ ] Real-time unified stream (WebSockets/SSE)
- [ ] OSS documentation (LICENSE, CONTRIBUTING, PRIVACY, TERMS)

---
Maintained by: Core Engineering  
Last Updated: January 12, 2025
