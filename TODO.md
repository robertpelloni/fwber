# TODO — fwber Immediate Action Items

> **Version:** 2.2.0
> **Last Updated:** 2026-06-10

---

## 🟢 Recently Completed: Phase 9 - Social Velocity & Federation (v2.2.0)
- [x] **WebFinger Discovery**: Implement real handle-to-URI resolution in `FederationService`.
- [x] **ActivityPub Inbound**: Implement `handleCreate` to ingest remote posts.
- [x] **Federated Search**: Update Search UI/API to perform live remote lookups.
- [x] **Activity Center**: Polish unified feed for Likes, Boosts, and Posts.
- [x] **Federated Interactions**: Wire Like/Boost/Reply buttons to ActivityPub outbound triggers.
- [x] **Enhanced Actor Profiles**: Expose real profile data via the ActivityPub actor endpoint.
- [x] **Outbound Follow/Unfollow**: Complete the ActivityPub follow/unfollow handshake.
- [x] **Federated Mentions**: Notify users on remote @mentions.
- [x] **Following Feed**: Personalized feed filtered by outbound follow relationships.
- [x] **Outbox Persistence**: Track all delivered outbound activities.
- [x] **Federated Profiles**: Broadcast ActivityPub Update on profile changes.

## 🟢 Recently Completed: Phase 8 - Intelligent Match Refinement (v2.1.9)
- [x] **Narrative Compatibility**: AI subagent explains match reasons based on question data.
- [x] **Proximity Unification**: Merged real-time proximity history with matching scores (80/20).
- [x] **Expansion**: Scaled value questions to 100+ (108 total) including Cyber-Noir ethics.
- [x] **UI Wiring**: Integrated narrative reports into MatchInsights and Profile pages.
- [x] **Bug Fix**: Resolved 500 error on federation actor detail endpoint.

## 🟢 Recently Completed: OkCupid Matching Engine (v2.1.5)
- [x] **Geometric-Mean Heuristic**: Implemented mutual-satisfaction matching logic.
- [x] **Value-Based Questions**: Launched `/settings/matching` and seeded Cyber-Noir questions.
- [x] **Discovery Integration**: Injected compatibility scores into the recommendation feed.
- [x] **Site Stability**: Verified full regression suite and resolved frontend build regressions.

## 🟢 Completed: v2.1.6-v2.1.7 Repository Reconciliation
- [x] **Branch Merge**: Forward-merged feat/okcupid-matching-engine and feat/federation-hardening into main.
- [x] **Prisma Regeneration**: Resolved 60 TS errors from new models not in generated client.
- [x] **Frontend Fixup**: Resolved 20 TS errors from merge artifacts (duplicate axios, missing imports, type mismatches).
- [x] **Production DB Sync**: Created 6 new tables (autonomous_actions, autonomous_settings, matching_questions, matching_options, user_matching_answers, user_integrations).
- [x] **Matching Questions Seeded**: 10 OkCupid-style questions live in production.
- [x] **start.bat Updated**: Monorepo-aware dev launcher (backend :4000, frontend :3000).

## 🔴 Upcoming: Phase 9 - Social Velocity & Federation
- [ ] **ActivityPub Federation**: Complete end-to-end interop testing with local Mastodon/Pleroma nodes.
- [ ] **Email Infrastructure**: Configure Resend DNS records (MX, SPF, DKIM, DMARC) for email delivery.
- [ ] **AI Provider Keys**: Configure OpenRouter/OpenAI API keys for Wingman AI features.
- [ ] **Stripe Live Keys**: Switch from test to live Stripe keys for production payments.

[... Historical TODOs moved to ARCHIVE ...]
