# FWBer – Overall Project Analysis & Roadmap (2025-11-15)

This is a concise, high-signal overview for future contributors (human or AI). It captures the current state, architecture, and a pragmatic, minimal roadmap. It intentionally avoids deep dives—refer to the linked docs for details.

## Executive summary

- FWBer is a proximity-first dating/social platform with:
  - Laravel backend API (fwber-backend)
  - Real-time features (Mercure events are present; WebSocket/Mercure endpoints exist but need final doc polish)
  - AI-powered matching/recommendations and content assistance (rich docs exist; some endpoints remain to document)
  - Privacy/security emphasis (moderation, blocking/reporting, rate limiting, geo-spoof detection)
- OpenAPI documentation coverage is now broad and consistent; core endpoints are annotated and generated under `/docs`.
- Remaining focus should be feature-complete behavior and shipping, not more documentation.

## Architecture (high level)

- Backend: Laravel 11, PHP 8, located in `fwber-backend/`
  - Auth: JWT bearer (`bearerAuth` in OpenAPI)
  - Data: Eloquent models for users, profile, matches, photos, artifacts, messages, groups, moderation, etc.
  - Real-time: Mercure publisher present (e.g., `ProximityArtifactController`), chatrooms and group messaging implemented
  - Storage: Public storage for photos/media (thumbnails, processing)
  - Docs: L5-Swagger + swagger-php; sources curated in `fwber-backend/config/l5-swagger.php`
  - Schemas: Consolidated in `fwber-backend/app/Http/Controllers/Schemas.php` main file-level docblock (critical ordering detail)
- Orchestration/AI: Multiple documents and scripts at repo root (see references). These outline skills, orchestration, and MCP tooling.
- Ops: Docker compose files exist for dev/prod; scripts for deployment and diagnostics are present in repo root.

## Current status snapshot

- OpenAPI docs: generated successfully at `fwber-backend/storage/api-docs/api-docs.json`; UI at `/docs`.
- Tags standardized: Authentication, Profile, Dashboard, Matches, Messages, Physical Profile, Profile Views, Groups, Chatrooms,
  Bulletin Boards, Health, Analytics, Moderation, Photos, Proximity Artifacts, Relationship Tiers, Safety.
- Recently documented controllers (Nov 15, 2025):
  - Photos (upload/update/delete/reorder/list)
  - Proximity Artifacts (feed, CRUD-lite, flag, local pulse)
  - Relationship Tiers (progressive photo unlock)
  - Safety (block/report)
  - Group Messages (send, list, mark read, unread count)
- Real-time and AI endpoints exist; many are already implemented and partially documented.

## Minimal, pragmatic roadmap (ship-focused)

Near-term priorities (keep tight, avoid scope creep):

1) Finalize endpoint documentation where it aids integration/testing (short, not exhaustive):
   - Recommendations (AI)
   - WebSocket/Mercure endpoints (RT)
   - Content Generation (AI)
   - Rate Limit (Security)

2) Stabilize and verify production readiness:
   - End-to-end manual test flows: signup → profile → photos → matches → chat/groups → proximity artifacts
   - Validate auth/permissions (403/401 paths), input validation, and rate limits
   - Confirm storage (images/media) and thumbnail generation on all paths
   - Verify Mercure events in relevant user flows

3) Operational readiness:
   - Environment config review (keys, secrets, endpoints) using existing templates/scripts
   - Minimal monitoring/alerting checklist (HTTP uptime, error logs, queue/worker health if applicable)
   - Backups/retention for user-generated content

4) Final docs hand-off (lightweight):
   - Export Postman collection from OpenAPI
   - Short API index/README at `fwber-backend/docs/` linking to `/docs` and key controllers

## Risks and mitigations (selected)

- Schema resolution issues in swagger-php if schemas are moved: keep all component schemas in `Schemas.php` main file-level docblock
- Missing controllers from scan list: ensure new annotated files are added to `config/l5-swagger.php` → `annotations`
- Real-time visibility inconsistencies (shadow throttling, presence): add smoke tests and simple counters/metrics during QA
- Media handling edge cases (size/type/duration): confirm constraints match product expectations; add friendly validation messages

## Out of scope for now

- Deep architectural rewrites or migration of frameworks
- Overly granular API documentation beyond what is needed to integrate and test
- Non-critical feature expansions

## References (read these first)

- Backend (Laravel): `fwber-backend/`
  - Global OA + Tags: `fwber-backend/app/Http/Controllers/Controller.php`
  - Component Schemas: `fwber-backend/app/Http/Controllers/Schemas.php`
  - Swagger Config: `fwber-backend/config/l5-swagger.php`
  - Generated JSON: `fwber-backend/storage/api-docs/api-docs.json`
  - API Docs Roadmap (this session): `fwber-backend/docs/API_DOCUMENTATION_ROADMAP_2025-11-15.md`
- AI/Orchestration (root):
  - `AI_SKILLS_AND_ORCHESTRATION_COMPLETE_DOCUMENTATION.md`
  - `AI_SKILLS_COMPREHENSIVE_SUMMARY.md`
  - `AI_MODEL_INSTRUCTIONS_AND_COMMANDS.md`
  - `COMPREHENSIVE_DEVELOPMENT_STATUS_2025_01_19.md`
  - `final_mcp_verification.ps1`, `consolidated-mcp-server.js`, and related MCP config files
- Security/Advanced features (root):
  - `FWBER_ADVANCED_SECURITY_IMPLEMENTATION_COMPLETE.md`
  - `FWBER_ADVANCED_FEATURES_IMPLEMENTATION.md`

---

If you need more detail, consult the references above. Otherwise, proceed with the minimal roadmap to ship: finish light annotation on remaining endpoints, run end-to-end QA, lock environment, and cut a release.
