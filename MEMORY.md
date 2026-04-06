# MEMORY.md

## 2026-04-06 — v1.8.6 the branch now benefits from a dedicated plans hub because dating value includes execution, not just discovery
- Events, nearby, venues, deals, and date planning collectively represent the real-world follow-through layer of the product.
- Grouping them under one plans/meetups destination improves the product story: match -> plan -> meet.
- This hub complements places rather than replacing it; places remains location/discovery-oriented while plans emphasizes execution and outings.

## 2026-04-06 — v1.8.5 merchant tooling benefits from having its own commerce hub instead of being buried inside operations alone
- Merchant onboarding, dashboard, analytics, promotions, and vibe broadcasting are substantial enough to deserve their own product area.
- The operations hub is still useful for trust/control access, but a dedicated commerce hub makes business-side restoration much easier to evaluate.
- The branch is now reaching the point where most meaningful restored layers have a clear top-level landing page, which is improving perceived completeness dramatically.

## 2026-04-06 — v1.8.4 monetization and token-era features feel far more coherent once grouped as one economy surface
- Premium, wallet, referrals, boosts, gifts, and unlock mechanics were already restored enough to function, but they still read as isolated monetization remnants before this tranche.
- A dedicated economy hub makes it clearer that these are part of one business/access layer rather than unrelated pages.
- Using already-imported dashboard icons for new cards avoided the tiny missing-import seam that showed up in the prior two dashboard expansions.

## 2026-04-06 — v1.8.3 the profile and media layer becomes much more legible once identity controls are treated as a first-class hub
- Profile, photos, verification, and physical-profile settings were already present, but users still had to remember scattered routes to reach them.
- A dedicated identity hub helps the rewind branch feel closer to a finished product because self-presentation is central to the dating flow, not a side setting.
- The repeated missing-import build seams on new dashboard cards are trivial to fix and reinforce the value of running a full production build after every UI tranche.

## 2026-04-06 — v1.8.2 trust and control surfaces benefit from the same hub treatment as social and discovery systems
- Safety, settings, security, merchant, and moderation-adjacent routes felt restored but still operationally scattered before this tranche.
- A single operations hub improves branch legibility while also helping future testers find the product’s trust-sensitive controls quickly.
- The first build failure after this tranche was a narrow missing import, which reinforces that the current workflow of build-first validation catches UI integration seams cheaply.

## 2026-04-06 — v1.8.1 the restored branch benefits from a dedicated direct-social hub once messages and friend systems are alive again
- Messages, friends, activity, and notifications are individually restored, but they read as a more complete product once grouped into a shared connections destination.
- This hub is especially valuable because it makes the branch feel socially usable, not just feature-rich.
- Continuing to use low-risk hub pages is still the safest way to expand perceived completeness while backend CI remains healthy.

## 2026-04-06 — v1.8.0 AI and viral tooling become much easier to reason about once treated as a single studio surface
- Roast, roast-date, wingman, content generation, and bounties all read like part of the same playful growth/creator zone once exposed together.
- This hub adds meaningful product legibility without needing any risky backend changes, which fits the current rewind strategy extremely well.
- The green `v1.7.9` backend/frontend runs increase confidence that these incremental surface recoveries are not destabilizing the branch.

## 2026-04-06 — v1.7.9 the broader discovery/community surface also benefits from hub consolidation once the major leaf pages exist
- Recommendations, groups, topics, matches, and match-overview pages were all already present, but still fragmented enough to under-sell how much of the old surface had already come back.
- A scenes/discovery hub is a strong restore move because it links the branch’s social-discovery layer into a coherent destination without requiring new backend contract work.
- The hub pattern is now proving reusable across unlocks, spaces, places, reputation, and scenes.

## 2026-04-06 — v1.7.8 trust and social-proof surfaces also become much more legible when grouped into a reputation hub
- Achievements, leaderboard, profile views, and verification already existed, but were scattered enough to still feel partially restored.
- A dedicated reputation hub is another high-leverage way to make existing restored systems feel like a coherent product area without destabilizing runtime behavior.
- The same hub strategy is now clearly working across unlocks, spaces, places, and reputation clusters.

## 2026-04-06 — v1.7.7 the local-discovery / places cluster also benefits from a hub pattern, just like unlocks and spaces
- Nearby people, venues, deals, date planning, location settings, and safety are all related enough that they feel stronger as one restored destination.
- This keeps the richer branch legible as more systems come back online.
- Also, avatar-generation CI on the rewind branch may still depend on implicit-provider behavior, so locking testing fallback to DALL-E is a pragmatic compatibility move while the branch is being reconciled.

## 2026-04-06 — v1.7.6 normalization layers can leak machine-friendly values into prompt-building paths unless explicitly reformatted
- The rewind avatar-generation path uses profile interests that are normalized to lowercase for matching quality.
- That is good for recommendation logic, but bad for human-facing prompt assertions and prompt quality unless the value is reformatted before output.
- On the richer branch, fixing these small formatting leaks is part of CI convergence and also improves real generated prompt quality.

## 2026-04-06 — v1.7.5 live/community features also benefit from a hub pattern when the branch already contains many scattered route surfaces
- Chatrooms, audio rooms, bulletin boards, burner links, and pulse surfaces were already present, but still felt fragmented.
- A dedicated live-spaces hub is a strong restore move because it groups several already-restored systems into one top-level area without destabilizing backend/runtime behavior.
- This same pattern should keep being used for any remaining scattered feature clusters on the rewind branch.

## 2026-04-06 — v1.7.4 stale Eloquent relation caches can masquerade as missing-feature bugs on the rewind branch
- The remaining avatar-generation CI failure was likely not about providers anymore but about prompt generation reading a stale cached `profile` relation after the test created the profile in a separate statement.
- On the richer rewind branch, several systems create related rows after the base model exists, so direct relation queries are sometimes safer than trusting cached relation state in high-signal logic.
- This is another example of why branch-local compatibility fixes remain necessary even after large replay tranches succeed.

## 2026-04-05 — v1.7.3 the paywall/unlock cluster benefits from a hub pattern, not just many isolated pages
- Even after restoring boosts, gifts, referrals, and video as separate destinations, the token-gated unlock surfaces were still fragmented.
- A dedicated unlock hub is a higher-leverage restore shape because it turns several scattered routes into one coherent destination for the paywall-era product layer.
- This kind of hub is useful when the underlying systems are already present but still feel scattered from the user’s perspective.

## 2026-04-05 — v1.7.2 many "restored" systems still need real top-level destinations or they will continue to feel half-dead
- The rewind branch already contained boosts, gifts, referrals, and video functionality, but these systems still relied too heavily on scattered modals or inline triggers.
- Adding real pages for them is high leverage because it turns hidden implementation into visible restored product surface without destabilizing Hetzner deployment contracts.
- The restore strategy should keep pairing backend/contract recovery with page-level destination recovery so features are not only present, but actually reachable.

## 2026-04-05 — v1.7.1 some rewind-branch test failures are caused by testing shortcuts that are harmless in lean branches but wrong for full-surface suites
- The avatar-generation code was still using testing-only early returns when provider credentials were absent, which prevented `Http::assertSent()`-style rewind tests from ever observing outbound generation calls.
- Recommendation responses on the richer branch also needed their older tagged-cache contract restored because the CI suite asserts personalized recommendation caching explicitly.
- The right pattern on the rewind branch is to preserve observable contracts under `Http::fake()` / `Cache::shouldReceive()` rather than prematurely short-circuiting inside test environments.

## 2026-04-05 — v1.7.0 A restore branch can still feel broken even when the code is present if the shell highlights the wrong surfaces
- The rewind branch contained many restored systems, but the main app shell still pointed users toward excluded federation/journal-era branches and still lacked real `/activity` and `/notifications` destinations.
- Restoring breadth is not enough; the signed-in shell must also spotlight the approved surfaces so the product actually feels restored.
- The next rewind passes should keep checking for this pattern: code may exist, but if navigation and dashboard entry points are wrong, the branch will still appear half-restored.

## 2026-04-05 — v1.6.9 Restore-branch failures were mostly compatibility drift, not fresh feature bugs
- The rewind branch was closer to working breadth than it initially looked; the red CI signal was concentrated around a few old-assumption seams:
  - avatar generation prompt/config behavior no longer matched its tests
  - tagged-cache helper no longer invoked `Cache::tags()` under mocked expectations
  - frontend Sentry setup had fallen behind modern App Router conventions
  - frontend E2E crypto still assumed generated WASM bindings existed in every checkout
- Local restore-worktree validation after the fixes reached **425 passing backend tests** and a green production frontend build.
- The right next move is aggressive rewind reconciliation, not endless tiny selective restores.

## 2026-04-05 — v1.6.8 Discovery routes still need schema guards even when federation is de-scoped
- `/nodeinfo/2.0` was still 500ing live because `NodeInfoController` assumed `user_profiles.is_federated` existed.
- Even when federation is not the active product focus, discovery routes must degrade safely instead of crashing on absent optional schema.
- Frontend CI also needed runtime-family alignment: the lockfile/build was validated under Node 24 locally, so GitHub should match that instead of staying on Node 20.

## 2026-04-05 — v1.6.6 Daily-log shared write should use ACLs, not Monolog chmod from deploy user
- The first live deploy after the backend stability patch failed because Monolog's `permission` option tried to chmod a daily log file owned by `www-data` from a deploy-user artisan process.
- The correct repair shape is shared ACLs on `storage/logs` plus removing the Monolog permission override, not repeated per-file chmod attempts in deploy code.
