# TODO — fwber Immediate Action Items

> **Version:** 2.1.8
> **Last Updated:** 2026-06-08

---

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

## 🔴 Upcoming: Phase 8 - Intelligent Match Refinement
- [ ] **Narrative Compatibility**: Develop AI subagent to explain match reasons based on question data.
- [ ] **Proximity Unification**: Merge real-time proximity history with matching scores.
- [ ] **Expansion**: Scale value questions to 100+ via generative agents.
- [ ] **ActivityPub Federation**: Complete end-to-end interop testing with local Mastodon/Pleroma nodes.
- [ ] **Federation actors/detail endpoint**: Fix 500 error on /api/federation/actors/detail.
- [ ] **Email Infrastructure**: Configure Resend DNS records (MX, SPF, DKIM, DMARC) for email delivery.
- [ ] **AI Provider Keys**: Configure OpenRouter/OpenAI API keys for Wingman AI features.
- [ ] **Stripe Live Keys**: Switch from test to live Stripe keys for production payments.

[... Historical TODOs moved to ARCHIVE ...]
