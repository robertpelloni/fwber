# TODO — fwber Immediate Action Items

> **Version:** 1.0.74  
> **Last Updated:** 2026-04-02

---

## 🔴 Critical: Bug Fixes & Stability
- [ ] **Complete Stripe Production Rollout**: Billing now has backend-owned plan metadata, real Stripe proof requirements, a success return route, and renewal MLM payouts, but production still needs live env keys, frontend publishable key wiring, Stripe webhook registration, and an operations-ready payout/reconciliation checklist.
- [x] **Fix 500 Errors on Production**: Hardened `/api/location`, `/api/photos`, and `/api/safety/walk/active` against the likely DreamHost-only failure modes (event-store append outages, null legacy photo paths, and missing safety tables) instead of relying on the already-present global exception logging.
- [ ] **Verify Vercel Deployment**: Ensure the latest Next.js build with the `/api` absolute path proxy and MIME fixes is successfully deployed and running.

## 🟡 High: Missing UI Integrations
- [x] **ActivityPub Federation Protocol Hardening**: Federation UI now covers search, follow relationships, actor exploration, activity center, global feed, public outbox visibility, follow accept state transitions, inbound inbox signature verification, local actor key generation, and signed outbound delivery.
- [ ] **Merchant Portal UI**: Merchant registration, promotion creation, management, analytics, vibe analysis, live pulse broadcasting, and broadcast history are now wired, but the portal still needs deeper lifecycle tools like resend/deactivate/reporting controls and broader operations polish.
- [x] **Scene Discovery Phase 5**: Followed the topic-hub slice with topic-aware match ranking, richer profile scene cards, and scene overlap that uses interests plus visible social context instead of pure radius.
- [x] **Recommendation Scene Signals**: Extended the recommendation hub and personalized feed with scene-aligned recommendation metadata and card rendering on top of the shared topic graph.
- [x] **Local Pulse Scene Signals**: Extended Local Pulse artifacts/promotions with shared scene metadata, pulse card rendering, and regression coverage on top of the same topic graph.
- [x] **Local Pulse Ranking / Trust Signals**: Moved beyond Local Pulse card cues so the feed ranking itself now uses scene signals together with privacy-safe friend/circle/relationship context.
- [x] **Recommendation Ranking / Trust Signals**: Extended recommendation ordering and personalized feed explanations with the same privacy-safe trust-aware scoring model already used by Local Pulse.
- [x] **Nearby Chatroom Ranking / Trust Signals**: Extended nearby proximity chatroom discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Event Ranking / Trust Signals**: Extended nearby event discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Bulletin Board Ranking / Trust Signals**: Extended nearby bulletin board discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Group Matching Ranking / Trust Signals**: Extended group matching discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Venue Ranking / Trust Signals**: Extended nearby venue discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Nearby User Ranking / Trust Signals**: Extended nearby people discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Audio Room Ranking / Trust Signals**: Extended the audio-room lobby with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Deal Ranking / Trust Signals**: Extended nearby deals discovery with the same privacy-safe trust-aware scoring model, fixed the coupled merchant relation contract, and added high-level ranking explanation.
- [x] **Chatroom Ranking / Trust Signals**: Extended chatroom browse discovery with the same privacy-safe trust-aware scoring model and high-level ranking explanation.
- [x] **Sidebar Shell Sweep**: Moved the remaining requested community and discovery pages onto the shared `AppHeader` shell and promoted `/federation` as the primary federation route.
- [x] **Federation Outbox Visibility**: Replaced the placeholder public outbox with a real ActivityStreams collection backed by active board posts, added a dedicated outbox page, and surfaced it in the federation activity center.
- [ ] **AI Wingman Enhancements**: The backend supports `compatibilityAudit`, `findNemesis`, and `predictFortune`. Ensure these are fully wired up in the chat interface with rich, animated UI components.
- [ ] **Hardware Token UI Polish**: Ensure the `app/settings/hardware/page.tsx` gracefully handles BLE pairing flows and visualizes the "ping" actions.

## 🟢 Medium: Robustness & Refactoring
- [ ] **Event Sourcing Audit**: Review all `EventStore::append` calls to ensure aggregate IDs are strictly cast to strings and that no integer-to-string database constraint errors exist.
- [ ] **Offline Sync Conflict Resolution**: Enhance `use-chat-sync.ts` to handle complex CRDT conflict resolution when merging offline messages with server state.
- [ ] **Rust Geo-Screener Integration**: Transition the PHP `LocationController` to offload dense spatial queries to the `fwber-geo` Rust microservice.

## ⚪ Low: Technical Debt & Documentation
- [ ] Update `cypress` E2E tests to cover the new ZK-Identity and AR Navigation flows.
- [ ] Clean up unused local test files and outdated `.txt` debug logs from the root directory.
- [ ] Ensure all new API endpoints are fully documented with Swagger/OpenAPI annotations.

## ✅ Recently Completed
- [x] **ActivityPub Signed Outbound Delivery**: Added a dedicated ActivityPub keypair service, widened `user_public_keys` for multiple key types, exposed real actor public keys, and replaced mocked outbound follow dispatch with signed remote inbox delivery.
- [x] **ActivityPub Inbox Signature Verification**: Added inbound HTTP signature middleware for `/api/federation/users/{id}/inbox`, enforced `Signature`/`Date`/`Digest` verification against remote actor keys, and covered the path with signed-request regression tests.
- [x] **Premium Billing Hardening**: Removed the unsafe mock Stripe fallback, restored `/premium` and `/settings/subscription` to the Stripe upgrade modal, fixed webhook secret lookup, corrected subscription amount rendering, and added visible referral-loop copy on the homepage.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
