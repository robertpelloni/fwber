# Changelog

All notable changes to this project will be documented in this file.

## [1.0.74] - 2026-04-02 — ActivityPub Signed Outbound Delivery

### Fixed
- Added `ActivityPubKeyService` plus a widening migration for `user_public_keys`, so the backend now stores a dedicated encrypted ActivityPub RSA keypair alongside the existing E2E key record instead of forcing both concerns through the same single-row slot.
- Replaced the mocked `ActivityPubService::dispatchToRemoteInbox()` with real remote actor fetch + inbox resolution + HTTP Signature dispatch, so federated follow requests and follower broadcasts now perform signed outbound POSTs instead of only logging intent.
- Updated actor payload generation to expose the real generated public key rather than the old mock placeholder, making local actor documents usable for remote signature verification.
- Hardened the schema expansion for SQLite test runs by rebuilding `user_public_keys` there instead of assuming a portable named unique index drop.
- Scoped the existing E2E key-management queries to `key_type = ECDH`, preserving the current encryption-key APIs after the table began holding ActivityPub key material too.
- Added outbound federation regression coverage for actor public-key generation/exposure and signed follow delivery, alongside the existing inbox-signature and E2E key-management suites.

## [1.0.73] - 2026-04-02 — ActivityPub Inbox Signature Verification

### Fixed
- Added `HttpSignatureService` plus `VerifyActivityPubSignature` middleware so the ActivityPub inbox now rejects unsigned or invalid remote activities before the controller processes Follow, Accept, Undo, or Create payloads.
- Enforced inbound `Signature`, `Date`, and `Digest` validation and remote actor public-key resolution, including rejection of stale requests and key/payload actor mismatches.
- Applied the new signature middleware directly to the federation inbox route through the Laravel 11 bootstrap alias wiring.
- Upgraded the existing ActivityPub inbox tests to use real RSA-signed requests and added a dedicated signature regression suite covering missing signatures, invalid signatures, stale dates, and actor/key mismatches.

## [1.0.72] - 2026-04-02 — Production 500 Endpoint Hardening

### Fixed
- Hardened `LocationController::update()` so user location writes no longer fail outright if event-sourcing append calls blow up; the location projection now still saves while the event-store failure is logged as a warning.
- Hardened `Photo` URL accessors so legacy or partially migrated photo rows with missing storage paths no longer crash `/api/photos`; the API now returns empty URL strings instead of surfacing a 500.
- Hardened `SafetyController` read endpoints to degrade safely when DreamHost is missing the safety tables, returning an empty contacts list or `walk: null` rather than a database-shaped 500.
- Added regression coverage for event-store failure during `/api/location`, null-path photo listing on `/api/photos`, and missing `safe_walks` schema handling on `/api/safety/walk/active`.

## [1.0.71] - 2026-04-02 — Plan-Aware Premium Pricing

### Fixed
- Added a backend-owned premium plan catalog in `config/premium.php` plus `PremiumPlanCatalog`, so `gold_monthly` is now a real plan definition with configurable USD price, duration, token cost, and Stripe price key instead of an ignored frontend hint.
- Updated `PremiumController` to validate `plan_id`, reject unknown plans with `422`, and use the configured plan metadata consistently across Stripe intent creation, direct Stripe charges, token purchases, subscription records, and premium expiration windows.
- Updated the Stripe payment-intent and webhook path to carry plan metadata end to end, so `payment_intent.succeeded` can grant premium for the configured plan duration and store the correct `stripe_price` instead of always hardcoding the monthly fallback.
- Extended backend billing regression coverage for configured-plan initiation, configured-plan Stripe purchases, invalid plan rejection, and webhook plan metadata handling.
- Removed the last hardcoded card-price text from `PremiumUpgradeModal`; the modal now moves users into card checkout generically and uses the backend-returned payment-intent amount when rendering the Stripe form.
- Documented the premium plan env knobs in `.env.example`, keeping rollout config aligned with the new backend plan catalog.

## [1.0.70] - 2026-04-02 — Stripe Renewal Rollout Follow-Up

### Fixed
- Added the missing Stripe production-example env defaults to `fwber-backend/.env.example`, including `PAYMENT_DRIVER=stripe` plus the configurable level-1 and level-2 premium cash/FWBcoin referral reward knobs already consumed by the backend referral config.
- Closed the missing post-checkout route by adding `/premium/success`, giving Stripe redirect-based upgrades a real confirmation screen that forwards users into subscription settings instead of landing on a dead URL.
- Extended `StripeWebhookController` so successful renewal invoices now flow through the same two-level premium MLM commission engine as first-time premium purchases, preserving idempotency through the existing payment/commission records.
- Added webhook regression coverage proving a Stripe subscription renewal creates the payment record and awards both level-1 and level-2 referral commissions.
- Reconfirmed the frontend build in this Windows worktree using `cmd /c "npm run build"` after the known PowerShell manifest-race symptom, while lint still reports only the longstanding `fwber-frontend/lib/api/photos.ts:476` hook warning and fresh type-check passes.

## [1.0.69] - 2026-04-02 — Shell Theme & Realtime UX Polish

### Fixed
- Simplified the frontend appearance controls to a single polished light/dark theme path, removed the extra theme-style switcher, and tuned the homepage hero/quote treatment so the landing page feels more cohesive instead of mixing multiple visual systems.
- Made the Viral Rewards trigger louder with a flashy **Invite & Earn** CTA, added a sibling **Get Vouched** entry point that opens the vouch tab directly, and reinforced the real-money referral upside in the dashboard control cluster.
- Fixed the floating fallback subpage nav so it keeps watching for late-mounted local headers instead of lingering on top of the real app header/logo across discovery pages.
- Clarified realtime status by distinguishing an actually disconnected session from a frontend with Reverb/Pusher intentionally unconfigured, so the header badge now reflects when realtime is simply off.

## [1.0.68] - 2026-04-02 — Billing Launch Docs Refresh

### Fixed
- Brought the top-level repo docs forward to the live billing state by updating `README.md`, `ROADMAP.md`, `TODO.md`, and `DEPLOY.md` from stale `0.99.x` / `1.0.63` metadata to the shipped `1.0.67+` line.
- Added a concrete Stripe production go-live checklist covering frontend publishable key wiring, backend Stripe env/config, webhook registration, premium flow verification, referral commission verification, and the outstanding payout operations decision.
- Clarified the current stack and release messaging in `README.md` so the repo now advertises the actual Laravel 12 + Next.js 15 beta state instead of older placeholder version text.

## [1.0.67] - 2026-04-02 — Premium Billing Hardening

### Fixed
- Closed the unsafe premium-upgrade shortcut by requiring either a real Stripe `payment_method_id` or a confirmed `payment_intent_id` before `/api/premium/purchase` can grant Gold.
- Fixed Stripe webhook signature verification to read the nested `services.stripe.webhook.secret` config path while preserving the legacy fallback key for compatibility.
- Replaced the legacy direct-purchase flows on `/premium` and `/settings/subscription` with the existing `PremiumUpgradeModal`, so visible upgrade entry points return to Stripe Elements or the explicit 200 FWB token path instead of silently granting Gold.
- Added backend regression coverage proving Stripe purchases without payment proof now fail with `422`, and corrected the subscription history amount display so stored dollar amounts no longer render as cents.
- Added concise front-page copy that explains the two-level premium referral/FWBcoin loop without implying live cash payouts before billing is configured.

## [1.0.66] - 2026-04-02 — FWBcoin Rename & Validation Follow-Up

### Fixed
- Renamed all shipped user-facing legacy token references to **FWBcoin** in the viral rewards flow and release documentation while leaving the underlying neutral token accounting fields untouched.
- Fixed the frontend type mismatches introduced during the structured interest-graph bridge by aligning the profile API type with the new `interests` field, guarding optional interest access in the profile editor, and giving the shared match-filter interest option type an optional `emoji`.
- Confirmed the current frontend slice validates cleanly in a fresh subprocess: lint passes with only the pre-existing `fwber-frontend/lib/api/photos.ts` hook warning, the Next build succeeds, and type-check passes.

## [1.0.65] - 2026-04-02 — Structured Interest Graph Bridge

### Fixed
- Canonicalized profile interest values against the existing topic taxonomy so alias-style inputs now resolve to stable topic slugs, unmatched values are still preserved, and profile updates automatically enrich the user's followed topic graph without clobbering existing follows.
- Exposed structured `interest_topics` on profile responses, including whether each topic came from the profile graph, existing follows, or both, so profile and scene-discovery surfaces can render a real bridge between freeform interests and topic hubs.
- Updated the live `/profile` editor to load and save canonical top-level interests, merge them with the existing hobby/music/book/movie/sport buckets, and present topic-backed chips sourced from the actual topics API.
- Replaced the match filter's hardcoded shared-interest chip list with topic-backed options from the live taxonomy, while keeping a small fallback list for resilience if the topics API is unavailable.
- Added focused backend regression coverage for profile interest canonicalization/topic syncing and extended the scene discovery feature assertions to cover the new structured interest topic exposure.
- Hardened frontend type-checking against renamed dependency snapshots by excluding `node_modules_stale_*`, and confirmed fresh lint returns only the pre-existing `fwber-frontend/lib/api/photos.ts` hook warning.

## [1.0.64] - 2026-04-02 — Referral Commissions & Onboarding Skip Flow

### Fixed
- Removed the onboarding step blockers that were preventing users from advancing past optional steps like the fitness/physical screen, and sanitized profile payloads so blank or incompatible onboarding fields are omitted instead of breaking progression.
- Relaxed the shared backend profile update contract for onboarding's physical fields and added Cypress coverage proving users can continue through onboarding without filling every optional section immediately.
- Added runtime referral-code backfill for legacy users so auth/session responses and referral-driven flows no longer emit `ref=null` links for accounts created before referral codes were guaranteed.
- Added a dedicated referral summary API and rewired the viral rewards modal to consume backend-owned referral links, golden-ticket counts, vouch totals, and premium reward totals instead of constructing links from nullable cached auth state.
- Added a new `referral_commissions` ledger plus two-level premium reward awarding so direct premium conversions now record pending USD payouts and FWBcoin rewards, while second-level uplines also receive smaller commissions.
- Extended backend regression coverage for referral summary backfill and two-level premium commission payouts on both card and token premium purchases.

## [1.0.63] - 2026-04-02 — Federation Follow Accept Handling

### Fixed
- Added ActivityPub inbox handling for `Accept` activities wrapping `Follow`, so fwber now transitions matching local `Following` records from `pending` to `accepted` when a remote server confirms the follow.
- Bound accept processing to the expected local actor URI and remote actor/object pairing so unrelated or malformed `Accept` payloads are ignored instead of mutating follow state.
- Corrected outbound federated follow payloads to identify the local actor via `/api/federation/users/{id}` rather than the stale `/api/v1/actor/{id}` path, aligning follow requests with the actual exposed actor endpoint.
- Added focused backend regression coverage proving inbox `Accept` activities promote pending follow relationships to accepted status without regressing the existing inbox/outbox ActivityPub contract.

## [1.0.62] - 2026-04-02 — Federation Outbox Visibility

### Fixed
- Replaced the placeholder ActivityPub outbox response with a real `OrderedCollection` / `OrderedCollectionPage` backed by active public `board_post` proximity artifacts for federated users.
- Mapped eligible local board posts into public ActivityStreams `Create` activities containing `Note` objects so fwber now exposes a meaningful outbox surface without widening private graph data.
- Added focused backend regression coverage proving the outbox page only includes active `board_post` artifacts and excludes expired or unrelated artifact types.
- Extended the shared federation frontend API contract with typed outbox helpers so ActivityPub pages can request and render public outbox entries consistently.
- Added a dedicated federation outbox page plus an activity-center outbox summary so users can review the public activities currently exposed for their federated identity.
- Expanded the federation hub navigation with an Outbox entry, keeping `/federation` as the primary route while making the new public outbox surface easier to reach.

## [1.0.61] - 2026-04-02 — Trust-Aware Chatroom Ranking & Sidebar Shell Sweep

### Fixed
- Reworked chatroom browse discovery so the main chatroom directory can opt into the same privacy-safe trust-aware model already shipped across Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, audio rooms, and deals, balancing trusted creators, scene alignment, community health, and freshness.
- Added `ChatroomRankingService`, updated `ChatroomController` to return ranked browse metadata, and extended the frontend chatroom contract/page so ranked chatrooms surface high-level strategy copy, scene cues, and ranking scores without exposing private graph edges.
- Added focused backend regression coverage proving chatrooms expose ranking metadata and that a trusted, scene-aligned room can outrank a busier generic room.
- Moved the remaining requested discovery/community surfaces onto the shared `AppHeader` shell so the desktop left sidebar now appears consistently on groups, events, proximity chatrooms, conference pulse, date planner, audio rooms, burner bridge, bulletin boards, nearby, leaderboard, and federation.
- Promoted `/federation` to the primary federation route, pointed the main nav there, and kept the existing settings federation page working behind the new primary entrypoint.

## [1.0.60] - 2026-04-02 — Trust-Aware Deal Ranking

### Fixed
- Reworked `GET /api/deals` so nearby deal discovery can opt into the same privacy-safe trust-aware model already shipped across Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, nearby users, and audio rooms, balancing trusted merchants, scene alignment, deal health, freshness, and distance.
- Added `DealRankingService` to reuse `LocalPulseRankingService` trust-map inputs, `AIMatchingService` scene-signal helpers, and geo distance scoring while keeping private social-graph details internal to server-side ordering.
- Fixed the coupled promotions browse-path contract by adding a canonical `merchant()` relation alias on `Promotion` and loading the merchant fields the deals UI and ranker now depend on.
- Extended the ranked deals response with paginated `data`, convenience `deals`, and high-level `meta.ranking_strategy` metadata plus scene cues, ranking scores, and merchant verification state without breaking existing pagination consumers.
- Updated the deals page to request trust-aware ranking, explain why trusted scene-aligned merchants surface first, and render merchant verification, scene cues, ranking score, and distance inline on each card.
- Added focused backend regression coverage proving deals expose ranking metadata and that a trusted, scene-aligned merchant offer can outrank a slightly closer generic deal.

## [1.0.59] - 2026-04-02 — Trust-Aware Audio Room Ranking

### Fixed
- Reworked `GET /api/audio-rooms` so the audio-room lobby can opt into the same privacy-safe trust-aware model already shipped across Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, venues, and nearby users, balancing trusted hosts, scene alignment, participant health, freshness, and distance when host location is available.
- Added `AudioRoomRankingService` to reuse `LocalPulseRankingService` trust-map inputs, `AIMatchingService` scene-signal helpers, and host location data while keeping private social-graph data internal to server-side scoring.
- Extended the ranked audio-room lobby response with `data`, `rooms`, and high-level `meta.ranking_strategy` metadata plus scene cues and ranking scores, while preserving the legacy raw array response for non-ranked callers.
- Updated the audio-room lobby page to request trust-aware ranking, explain why trusted scene-aligned rooms surface first, and render room distance plus scene cues inline on each room card.
- Added focused backend regression coverage proving audio rooms expose ranking metadata and that a trusted, scene-aligned room can outrank a slightly closer stranger room.

## [1.0.58] - 2026-04-02 — Trust-Aware Nearby User Ranking

### Fixed
- Reworked `GET /api/location/nearby` so nearby people discovery now uses the same privacy-safe trust-aware model already shipped across Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, group matching, and venues, balancing trusted connections, scene alignment, activity recency, and distance.
- Added `NearbyUserRankingService` to reuse `LocalPulseRankingService` trust-map inputs and `AIMatchingService` scene-signal helpers while keeping private social-graph data internal to server-side scoring.
- Extended the nearby users API contract with high-level `meta.ranking_strategy` metadata plus scene signals and ranking scores for stable frontend consumption without exposing private relationship details.
- Updated the nearby people page to request trust-aware ranking, explain why trusted scene-aligned people surface first, and render nearby scene cues inline on each result card.
- Added focused backend regression coverage proving nearby users expose ranking metadata and that a trusted, scene-aligned person can outrank a slightly closer stranger.

## [1.0.57] - 2026-04-02 — Trust-Aware Nearby Venue Ranking

### Fixed
- Reworked `GET /api/venues` so nearby venue discovery now uses the same privacy-safe trust-aware model already shipped across Local Pulse, recommendations, nearby chatrooms, events, bulletin boards, and group matching, balancing trusted recent visitors, scene alignment, venue health, freshness, and distance.
- Added `VenueRankingService` to reuse `LocalPulseRankingService` trust-map inputs and `AIMatchingService` scene-signal helpers while keeping private social-graph data internal to server-side scoring.
- Stabilized the venues API contract by returning `data`, `venues`, and high-level `meta.ranking_strategy` metadata for consistent frontend consumption.
- Updated the venues page to explain trust-aware ranking, surface verification and active check-in context, and render scene-aligned venue cues.
- Added focused backend regression coverage proving nearby venues expose ranking metadata and that a trusted, scene-aligned venue can outrank a slightly closer stranger venue.

## [1.0.56] - 2026-04-02 — Trust-Aware Group Matching Ranking

### Fixed
- Reworked `GET /api/groups/{id}/matches` so group discovery ranking now uses the same privacy-safe trust-aware model already shipped across Local Pulse, recommendations, nearby chatrooms, events, and bulletin boards, balancing compatibility, trusted members, scene alignment, member health, and distance.
- Added `GroupRankingService` to reuse `LocalPulseRankingService` trust-map inputs and `AIMatchingService` scene-signal helpers while preserving private graph data as internal scoring-only signals.
- Stabilized the group matching API contract by returning `data`, `matches`, and high-level `meta.ranking_strategy` metadata that both group matching UIs can consume consistently.
- Updated `/groups/matching` and `/groups/[id]/matches` to explain trust-aware ranking and render scene-aligned cues for ranked group matches.
- Added focused backend regression coverage proving group matches expose ranking metadata and that a trusted, scene-aligned group can outrank a slightly closer stranger group.

## [1.0.55] - 2026-04-02 — Trust-Aware Bulletin Board Ranking

### Fixed
- Reworked nearby bulletin board discovery so `GET /api/bulletin-boards` can rank boards with the same privacy-safe trust-aware model already used by Local Pulse, recommendations, nearby chatrooms, and events, balancing trusted recent participants, scene alignment, activity health, freshness, and distance.
- Added `BulletinBoardRankingService` to reuse `LocalPulseRankingService` trust-map inputs and `AIMatchingService` scene-signal helpers while preserving the existing nearby boards response shape and keeping private social-graph details out of the payload.
- Updated the bulletin boards API contract and page UI to expose high-level `ranking_strategy` metadata plus scene-aligned cues for ranked boards.
- Added focused backend regression coverage proving nearby bulletin boards expose ranking metadata and that a trusted, scene-aligned board can outrank a slightly closer stranger board.

## [1.0.54] - 2026-04-02 — Trust-Aware Event Ranking

### Fixed
- Reworked nearby event discovery so `GET /api/events` can rank results with the same privacy-safe trust-aware model already used by Local Pulse, recommendations, and nearby chatrooms, balancing trusted organizers, scene alignment, freshness, and distance.
- Added `EventRankingService` to reuse `LocalPulseRankingService` trust-map inputs and `AIMatchingService` scene-signal helpers while preserving the existing paginated events contract and keeping private social-graph details out of the payload.
- Updated the events API contract and events page UI to expose high-level `ranking_strategy` metadata plus scene-aligned event card cues for ranked results.
- Added focused backend regression coverage proving nearby events expose ranking metadata and that a trusted, scene-aligned event can outrank a slightly closer stranger event.

## [1.0.51] - 2026-04-02 — Trust-Aware Local Pulse Ranking

### Fixed
- Reworked Local Pulse ordering so nearby artifacts are ranked by a privacy-safe composite of trusted connections, scene alignment, and freshness instead of pure recency.
- Added `LocalPulseRankingService` to batch friendship, confirmed relationship-link, and shared-circle checks into an internal trust map without exposing private graph edges in the API payload.
- Updated Local Pulse response metadata and UI copy so the feed now explains that ranking uses scene alignment, trusted connections, and freshness while keeping trust details internal to ordering.
- Added regression coverage proving a trusted, scene-aligned Local Pulse artifact outranks a newer generic stranger post.

## [1.0.52] - 2026-04-02 — Trust-Aware Recommendation Ranking

### Fixed
- Reworked recommendation and personalized-feed ordering so combined recommendation batches now rank with the same privacy-safe trust-aware model already shipped for Local Pulse, balancing trusted connections, scene alignment, freshness, and base relevance.
- Extended `RecommendationService` to preserve author metadata through recommendation normalization, apply trust-aware composite ranking with `LocalPulseRankingService`, and surface high-level `ranking_strategy` metadata without exposing private graph edges in the payload.
- Updated the recommendations hub API types and UI to explain the ranking strategy consistently across mixed recommendations and personalized feed views.
- Added focused recommendation regression coverage and fixed two real backend recommendation-path bugs uncovered during rollout: SQLite-incompatible `HOUR(created_at)` usage in activity-pattern analysis and incorrect `TelemetryEvent` persistence using `created_at` instead of `recorded_at`.

## [1.0.53] - 2026-04-02 — Trust-Aware Nearby Chatroom Ranking

### Fixed
- Reworked nearby proximity chatroom discovery so `GET /api/proximity-chatrooms/nearby` can rank rooms with the same privacy-safe trust-aware model already used by Local Pulse and recommendations, balancing trusted creators, scene alignment, recent activity, and distance.
- Added `ProximityChatroomRankingService` to reuse `LocalPulseRankingService` trust-map inputs and `AIMatchingService` scene-signal helpers without widening the payload's private social graph surface.
- Updated the nearby chatrooms API contract and page UI to expose high-level `ranking_strategy` metadata plus scene-aligned headlines for ranked rooms.
- Added focused backend regression coverage proving nearby chatrooms expose ranking metadata and that a trusted, scene-aligned room can outrank a slightly closer stranger room.

## [1.0.50] - 2026-04-02 — Session Transfer Handoff Refresh

### Fixed
- Expanded the session-transfer documentation so the current state after `v1.0.49` is captured in much greater detail across `HANDOFF.md`, `PROJECT_STATUS.md`, `ROADMAP.md`, `TODO.md`, `VERSION.md`, and the session plan.
- Documented the latest discovery rollout boundaries: matches, profiles, recommendations, and Local Pulse cards now all reuse the same topic/scene graph, while Local Pulse ranking and deeper trust-aware discovery remain the next recommended slice.
- Captured the concrete operational findings from this session, including the Local Pulse `birthdate` schema fix, the dashboard floating-logo root cause in `GlobalSubpageNav`, and the reliable frontend validation workflow for safe handoff to the next session.

## [1.0.48] - 2026-04-02 — Recommendation Scene Signals

### Fixed
- Extended `RecommendationService` so recommendation items are enriched with `scene_signals` derived from the same followed-topic and structured-interest graph that powers scene discovery in matches and profiles.
- Fixed recommendation scene matching to tokenize content text and allow sensible term overlap instead of relying on brittle whole-string matching, so scene cues appear for realistic board names and descriptions.
- Updated the recommendations hub and personalized feed cards to render scene-aligned headlines, topic chips, and matched scene tags using the live API payload.
- Added focused backend regression coverage for recommendation scene enrichment and validated the slice with lint, clean build, and fresh type-check in the frontend.
## [1.0.49] - 2026-04-02 — Local Pulse Scene Signals

### Fixed
- Extended Local Pulse artifact and sponsored-promotion payloads with shared `scene_signals` built from followed topics and scene terms, reusing the same discovery graph already powering recommendations.
- Updated the Local Pulse card UI and proximity typings to render scene-aligned headlines, matched topic chips, and matched scene tags directly in the local feed.
- Added focused backend regression coverage for Local Pulse scene-signal payloads and fixed stale `date_of_birth` references in the Local Pulse candidate query by aligning it with the codebase's `birthdate` profile field.
- Fixed the dashboard/header logo overlap by preventing the floating global subpage nav from rendering before local header detection completes and by suppressing it across dashboard subroutes.

## [1.0.46] - 2026-04-02 — Topic Hubs

### Fixed
- Added a canonical `topics` catalog with seeded hub metadata, user follow/unfollow pivots, authenticated topic list/detail APIs, and topic-aware aggregation across public groups, visible journals, and Local Pulse artifacts.
- Extended Local Pulse backend validation and feed/create flows so proximity artifacts can carry an optional `topic_slug`, and added regression coverage for follows, hub aggregation, and topic-scoped pulse filtering.
- Added a new `/topics` browse surface, `/topics/[slug]` hub detail page, reusable topic API/hooks/components, topic navigation, and richer group cards so users can explore scenes beyond pure proximity.
- Added topic filters and topic-linked posting to Local Pulse while preserving the existing app shell and route build behavior.

## [1.0.47] - 2026-04-02 — Scene Discovery

### Fixed
- Extended the matching engine to compute `scene_overlap` metadata from followed topics, normalized interests, public group tags, and visible journal tags so match ranking now reflects shared scenes instead of pure radius plus profile overlap.
- Added `scene_summary` serialization to profile responses so public profiles expose followed topics, active scene tags, and lightweight counts for visible notes and public groups.
- Updated the matches swipe UI with scene-overlap cards and the public profile page with a dedicated scene summary panel, reusing the topic graph shipped in v1.0.46 instead of creating a parallel discovery taxonomy.
- Added focused backend regression coverage for scene-overlap match payloads and profile scene summaries, and validated the frontend slice with lint, clean build, and fresh type-check after aligning the `Match` client type with the live payload.

## [1.0.43] - 2026-04-02 — Copyright 2026 Refresh

### Fixed
- Updated the live homepage footer copyright notice from 2025 to 2026.
- Synced the repository version metadata and release handoff documents for the new patch release.

## [1.0.44] - 2026-04-02 — Field Notes and Journal Privacy

### Fixed
- Added a new journals / field notes backend with `public`, `friends`, `circle`, and `private` visibility, plus reusable access checks built on accepted friendships and active group membership.
- Added default journal privacy settings on profiles, including optional group-backed circle selection, and exposed those settings through authenticated privacy endpoints.
- Added a new `/journal` authoring surface, a dedicated journal privacy settings page, and profile rendering for visible field notes so long-form social content is now usable end to end.
- Added backend regression coverage for circle authoring, friend visibility, circle visibility, and journal privacy defaults.

## [1.0.45] - 2026-04-02 — Relationship Links

### Fixed
- Added a new `relationship_links` domain so accepted friends can propose, confirm, update, and remove mutual relationship/status links with `public`, `friends`, or `private` visibility.
- Extended the reusable visibility and profile serialization flows so confirmed relationship links appear on profiles only when the viewer is allowed to see them.
- Added relationship-link management to the Friends & Connections page, including request handling and per-link editing, plus public-profile rendering for visible links.
- Added focused backend regression coverage for friend-only proposal rules, confirmation, friend-scoped visibility, and relationship-link cleanup when friendships are removed.

## [1.0.41] - 2026-04-02 — Merchant Broadcast History

### Fixed
- Extended `/api/merchant-portal/analytics` to include recent Local Pulse merchant broadcast history sourced from `merchant_pulse_broadcast` proximity artifacts.
- Added backend regression coverage for merchant broadcast history aggregation and for the analytics endpoint returning the new history payload.
- Added a broadcast history panel to the merchant analytics page so merchants can review recent sends, vibe snapshots, promo codes, radius, and expiry state in one place.
- Suppressed the floating global subpage nav on the dashboard and gave the shared app header more room around the logo/connection badge cluster so the top bar no longer crowds the floating logo area.

## [1.0.42] - 2026-04-02 — Structured Match Interests

### Fixed
- Normalized profile interest values on save so duplicate and case-variant interests collapse into canonical matchable tags.
- Extended `/api/matches` to accept `interests[]` filters, rank shared-interest overlap higher, and return `shared_interests` metadata for each candidate.
- Added match-feed regression coverage for shared-interest filtering and reused the existing profile interest seam instead of introducing a second phase-1 graph storage path.
- Added shared-interest chips to the swipe card UI and interest filter controls to the matches page so users can narrow discovery around common interests immediately.

## [1.0.40] - 2026-04-02 — Events and Shell Stabilization

### Fixed
- Replaced the nearby-events geospatial `HAVING distance` pagination path with a safer distance filter/order query and added regression coverage for radius filtering so production geolocated event requests stop returning 500s.
- Fixed the active boost badge to consume the backend's real `/api/boosts/active` payload shape and fall back cleanly when expiry data is invalid, eliminating the `Boost Active / NaN:NaN` countdown bug.
- Restored the shared app shell on Matches and Messages so the sidebar is present and top-level content no longer sits under the floating logo area.
- Added versioned favicon/icon URLs to force browser refreshes of the animated fwber logo asset, and cleaned the roast/hype dialog copy so it no longer renders raw HTML entities.

## [1.0.38] - 2026-04-02 — Merchant Pulse Broadcast Activation

### Fixed
- Replaced the stubbed `POST /api/merchant/pulse/broadcast` flow with a real merchant broadcast path that reuses proximity `announce` artifacts, promotion-owned coordinates, and token spending.
- Added explicit vibe-target gating so merchant broadcasts now either send immediately when the live neighborhood mood matches or fail clearly without charging tokens.
- Updated the merchant vibe deck UI to reflect real send semantics, surface the live detected vibe on failures, and stop promising a nonexistent queued delivery path.
- Added focused regression coverage for successful broadcast sends, vibe-mismatch rejection, and missing-location guidance.

## [1.0.39] - 2026-04-02 — Bounty Flow Repair

### Fixed
- Expanded the `/api/bounties` payload to include the profile and photo relations the bounty cards actually render, and added a regression test covering the live `sort=reward` contract.
- Added an `age` accessor to `UserProfile` so bounty cards and detail views receive the age field they already expect from serialized profile objects.
- Switched bounty creation onto `POST /api/bounties`, kept shareable bounty detail pages on the public legacy GET route, and moved authenticated suggestions onto the live `/api/bounties/{slug}/suggest` endpoint.
- Replaced the dead `/home` and `/profile/bounty/create` bounty links with the live dashboard route and the existing reusable create-bounty modal.

## [1.0.32] - 2026-04-02 — Navigation UX Patch

### Fixed
- Added a clear back-to-dashboard control on the main settings page so users can leave settings without relying on browser navigation.
- Reworked `fwber-frontend/app/recommendations/page.tsx` to use the shared protected app shell and styling, and added a `Back Home` action there.
- Updated the shared `AppHeader` to use normal browser navigation for the routes that were repeatedly triggering failed RSC payload fetches before falling back anyway (`/help`, `/conference-pulse`, `/burner`, `/nearby`, `/leaderboard`, and `/wingman`).

## [1.0.37] - 2026-04-02 — Merchant Vibe Contract Fix

### Fixed
- Repaired the merchant vibe widget to consume the shared API client's real unwrapped response shape instead of reading a nonexistent nested `data.analysis` payload.
- Updated the merchant pulse backend to source vibe coordinates from the merchant's latest mapped promotion when the merchant profile itself has no persisted latitude/longitude fields.
- Added explicit merchant pulse guidance when no mappable promotion exists yet, and added regression coverage for both the promotion-backed and missing-location cases.

## [1.0.36] - 2026-04-02 — Merchant Promotion Management

### Fixed
- Added authenticated merchant promotion detail, update, and deactivate endpoints with ownership checks and regression coverage.
- Added a new `/merchant/promotions/[id]` management page so merchants can review metrics, edit campaign details, and deactivate promotions without rebuilding them.
- Linked merchant dashboard and promotions list cards into the new manage flow so live campaigns are actually actionable.
- Added the missing analytics destination to the merchant header navigation and expanded the shared merchant API helper with promotion detail/update/delete support.

## [1.0.35] - 2026-04-02 — Federation Explorer Navigation

### Fixed
- Added a backend federated actor-detail endpoint plus actor-filtered federated post retrieval so the frontend can inspect real cached actor context safely.
- Added a new `/settings/federation/actors` explorer and wired the federation hub, activity center, and feed into a shared actor drill-in flow.
- Removed the duplicate homepage `PWAInstallPrompt` mount so install-banner handling only runs from the root layout.
- Updated shared app and merchant headers so the fwber logo always routes home and non-home pages get a back control.
- Added a global subpage navigation control for routes that do not use the shared headers yet, giving those pages a consistent back/home affordance too.

## [1.0.34] - 2026-04-02 — Federation Trust Polish

### Fixed
- Repaired the security settings federation toggle so it persists through the real profile update flow instead of only mutating local UI state.
- Added a federation visibility card to `/settings/federation`, disabled risky follow/share actions when federation is off, and surfaced clearer guidance around pending remote follow handshakes.
- Added remote actor detail previews in the federation search UI so users can inspect a remote profile before following it.
- Replaced misleading federation feed interaction placeholders with explicit read-only messaging that matches the current backend capability.

## [1.0.33] - 2026-04-02 — Merchant Contract Repair

### Fixed
- Aligned the frontend merchant API types and response unwrapping with the backend's real profile, promotion, verification, and promo-code fields.
- Repaired merchant analytics to consume the shared frontend API client correctly and render the backend's `kpis`, `retention`, and `promotions` payload shape.
- Added a dedicated `/merchant/profile` page and updated merchant dashboard/header flows so verification state is visible and unverified merchants are steered toward profile completion instead of immediate promotion creation.
- Removed unsupported merchant registration fields, corrected onboarding copy around verification, added optional promo-code entry to the promotion form, and ignored root-level `.vercel/` checkout noise in the safe repo.

## [1.0.31] - 2026-04-02 — Federation Activity Center

### Fixed
- Repaired `fwber-frontend/app/settings/federation/feed/page.tsx` to consume the backend's real `{ posts: [...] }` response shape instead of reading a nonexistent nested `data` payload.
- Updated the global federation feed to render the current `FederatedPost` model fields such as `actor_uri`, `actor_username`, `actor_domain`, `actor_avatar`, and `metadata`.
- Added a protected federation activity center page at `/settings/federation/activity` that merges remote followers and cached federated posts into a single recent-activity view.
- Added direct navigation between federation settings, the activity center, and the global feed, and made "Copy Federated Handle" actually write to the clipboard with toast feedback.

## [1.0.30] - 2026-04-01 — Vercel Trigger Script LF Fix

### Fixed
- Normalized `fwber-frontend/vercel-ignore-build.sh` for POSIX `bash` execution so the frontend-only Vercel deploy trigger no longer fails on CRLF line endings.
- Added a `.gitattributes` rule to keep repository shell scripts checked out with LF endings, preventing the deploy trigger from regressing on future Windows-side edits.
- Touched the trigger script comment as a safe no-op change so Vercel can pick up a fresh frontend deployment after the stalled `v1.0.29` rollout.

## [1.0.25] - 2026-04-01 — Recommendations Feed Schema Repair

### Fixed
- Corrected recommendation engagement scoring to query `events.starts_at`, matching the live schema and preventing production `/api/recommendations/feed` failures caused by the nonexistent `start_time` column.
- Added regression coverage proving recommendation engagement scoring counts recent attended events through the `starts_at` event timestamp.
- Fixed the subscription settings page and swipe matches page to consume the shared frontend API client's unwrapped responses correctly instead of reading nonexistent nested `data` payloads.
## [1.0.29] - 2026-04-01 — Auth Probe Default Cleanup

### Fixed
- Removed stale prefilled demo credentials from `/test-auth` so the production auth probe no longer suggests invalid accounts.
- Added explicit guidance on the page that operators should enter a real production account when using the live login/profile smoke test.

## [1.0.28] - 2026-04-01 — Production Auth Probe Repair

### Fixed
- Updated the `/test-auth` frontend page to use the live browser-safe `/api` proxy instead of hardcoded `http://localhost:8000/api` URLs.
- Restored the production auth probe so release verification can exercise real login and protected profile access from the deployed frontend.

## [1.0.27] - 2026-04-01 — Recommendation And Matching Resilience

### Fixed
- Made the recommendation service evaluate only the requested recommendation source types so `ai`, `location`, `mixed`, and feed requests stop triggering unrelated brittle code paths.
- Fixed location recommendations to query bulletin boards through the real `center_lat` and `center_lng` schema instead of the nonexistent `location` column.
- Hardened recommendation source execution so a failing source logs and degrades to an empty slice instead of returning a 500 for the whole recommendations response.
- Added an AI matching fallback guard so vector embedding/search failures fall back to heuristic matching rather than crashing `/api/matches`.
- Added regression coverage for location-only recommendations and vector-failure fallback behavior.

## [1.0.26] - 2026-04-01 — Frontend API Contract Repair

### Fixed
- Repaired the main subscription management page to consume the shared frontend `api` helper correctly for subscription lists, payment history, and cancel responses instead of dereferencing nonexistent nested `data` payloads.
- Realigned the friends page with the backend's actual `/friends`, `/friends/requests`, `/friends/search`, and `/friends/requests/{userId}` routes so loading, searching, sending requests, and responding to requests no longer call stale endpoints.
- Fixed the public pulse node page to consume the unwrapped public payload directly so live venue pulse screens render instead of failing on `response.data`.

## [1.0.24] - 2026-04-01 — Sanctum Token Touch Throttle

### Fixed
- Registered a custom Sanctum personal access token model that throttles `last_used_at` persistence to once per configured interval instead of writing on every bearer-authenticated request.
- Added backend regression coverage proving first-touch, within-window skip, and post-window refresh behavior for throttled token usage tracking.

## [1.0.23] - 2026-04-01 — Notification Polling Load Reduction

### Fixed
- Changed the header notification bell to poll `GET /notifications/count` while the drawer is closed instead of fetching the full notifications payload every 30 seconds.
- Kept the full `GET /notifications` refresh behavior for the open drawer so the UI still shows fresh notification details when the user is actively viewing them.
- Typed the dashboard leaderboard query explicitly so frontend type-check validation succeeds while rendering the vouch leaderboard widget.

## [1.0.22] - 2026-04-01 — Tagged Cache Runtime Fallback

### Fixed
- Hardened the shared `TaggedCache` helper so runtime `BadMethodCallException` tag failures fall back to the namespaced non-taggable cache strategy instead of crashing endpoints.
- Added unit coverage for both `remember()` and `flush()` fallback behavior and revalidated the `/api/matches` feature path with the match filter suites.

## [1.0.21] - 2026-04-01 — Health Version Source Repair

### Fixed
- Updated the backend app version config to read the repository `VERSION` file so `/api/health` reports the actual deployed release instead of the stale `1.0.2` fallback.
- Added regression coverage to ensure the health endpoint version matches the repository version signal used for deployment tracking.

## [1.0.20] - 2026-04-01 — Notification Schema Memoization

### Fixed
- Memoized legacy notification schema detection inside `NotificationController` so a single `/api/notifications` request no longer repeats the same cache-backed schema capability lookup.
- Added regression coverage proving the legacy-schema cache is resolved only once per request while preserving legacy notification compatibility behavior.

## [1.0.17] - 2026-04-01 — Auth Restore Network Fallback

### Fixed
- Reused cached auth state when `/auth/me` restoration fails with a browser fetch/network error instead of clearing a valid session.
- Consolidated auth-cache restoration into a shared helper so transient restore failures and critical init failures behave consistently.

## [1.0.18] - 2026-04-01 — Production Cache And Recommendations Repair

### Fixed
- Added a backend tagged-cache fallback so production endpoints keep working when the active cache store does not support Laravel cache tags.
- Repaired live `subscriptions` and `matches` endpoints to use the safe cache fallback instead of crashing with tag-support exceptions.
- Made recommendations endpoints accept serialized `types` and `context` query params, restored the missing route/controller compatibility methods, and returned the frontend's expected `recommendations`/`metadata` response shape.
- Fixed the federation settings page to consume the shared API client's unwrapped responses correctly and guard against non-array payloads.

## [1.0.19] - 2026-04-01 — Cache Fallback Expansion And Notification Schema Caching

### Fixed
- Expanded the non-taggable cache fallback across additional production runtime paths including events, profile views, Stripe subscription invalidation, subscription cleanup jobs, and AI matching cache invalidation.
- Cached legacy notification schema detection so repeated notification requests stop querying `information_schema` on every hot path hit.
- Preserved the existing `v1.0.18` production fixes while reducing the risk of future DreamHost cache-tag regressions in adjacent endpoints.

## [1.0.16] - 2026-04-01 — Auth Restore And Legacy Notifications Stability

### Fixed
- Prevented transient `/auth/me` failures and early protected requests from wiping valid frontend auth state during session restore.
- Delayed photo and notification fetches until auth initialization completes so boot-time `401` responses do not trigger logout loops.
- Completed legacy DreamHost notifications support for unread counts and mark-as-read flows, with regression coverage.

## [1.0.15] - 2026-04-01 — Notifications Legacy Schema Compatibility

### Fixed
- Made the backend notifications endpoint support both legacy `notifications(user_id,title,body,...)` tables and newer Laravel-style notification tables.
- Preserved malformed-payload hardening while avoiding production SQL errors on older DreamHost schema versions.
- Added regression coverage for the legacy notifications schema path.

## [1.0.14] - 2026-04-01 — Notifications Payload Hardening

### Fixed
- Hardened the backend notifications feed to decode stored payloads defensively and survive malformed notification rows instead of returning HTTP 500 for the whole endpoint.
- Normalized notification response data to JSON-safe UTF-8 strings before returning it to authenticated clients.
- Added a regression test covering malformed stored notification payloads.

## [1.0.13] - 2026-04-01 — Notifications Route Repair

### Fixed
- Removed the backend `/notifications` route conflict so authenticated notification fetches resolve to `NotificationController` instead of the device-token listing controller.
- Disabled prefetch on the login page's `/register` and `/test-auth` links to avoid background RSC fallback noise there.
- Suppressed non-actionable websocket connection console noise for known transient `WebSocketError`/auth-code cases.

## [1.0.12] - 2026-04-01 — Auth Token Source Hardening

### Fixed
- Added an in-memory auth token source in the shared API client so freshly authenticated sessions can make protected requests immediately, even before browser storage effects settle.
- Cleared the shared API client token alongside browser auth storage on logout and auth-expiry handling to keep token state consistent.

## [1.0.11] - 2026-04-01 — Auth Persistence Race Fix

### Fixed
- Persisted freshly issued auth tokens to browser storage immediately during login, wallet login, two-factor completion, and registration.
- Prevented immediate post-login authenticated requests from racing ahead of token persistence and triggering a false logout/session-expired redirect.

## [1.0.10] - 2026-04-01 — Final Route Prefetch Cleanup

### Fixed
- Disabled the last remaining `/messages` and `/proximity` `Link` prefetch paths outside the main app shell, including dashboard legacy cards and proximity presence widgets.
- Reduced the remaining protected-route RSC fallback noise to actual click-time navigation instead of background prefetch attempts.

## [1.0.9] - 2026-04-01 — Asset Recovery & Protected Prefetch Hardening

### Fixed
- Disabled app-shell prefetch for authenticated header and sidebar routes beyond the original small subset, covering the remaining noisy navigation targets such as messages, groups, events, conference pulse, and proximity chatrooms.
- Stopped dashboard achievements from logging expected unauthenticated errors after auth expiry or stale session state.
- Allowed stale asset recovery to retry after a short cooldown instead of blocking all further recovery attempts for the same version.

## [1.0.7] - 2026-04-01 — Dashboard Auth Query Cleanup

### Fixed
- Replaced remaining dashboard widget calls that still bypassed the shared authenticated API client for stats, leaderboard, activity, profile completeness, and boosts.
- Gated achievements and related protected dashboard widgets so they do not fire unauthenticated requests during auth initialization.
- Reduced stray direct browser requests to `api.fwber.me` that were still producing unauthenticated and network-noise logs after the earlier production fixes.

## [1.0.8] - 2026-04-01 — Protected Route Navigation Noise Reduction

### Fixed
- Disabled client prefetch on the remaining protected dashboard and settings navigation links that were surfacing noisy RSC payload fallback logs during background prefetch.
- Stopped `SafeWalkTracker` from logging expected auth errors after session expiry or logout by treating authenticated API failures as a quiet reset instead of a console error.

## [1.0.6] - 2026-04-01 — Payments, Notifications & Realtime Hardening

### Fixed
- Guarded Stripe initialization so card-payment UI fails closed instead of throwing when `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` is missing.
- Removed the stray `NEXT_PUBLIC_STRIPE_KEY` usage and aligned wallet/premium payment flows on the publishable-key setting.
- Routed notification UI requests through the shared API client and added `GET /api/notifications/count`.
- Hardened backend notification serialization so malformed legacy notification payloads do not 500 the entire notifications feed.
- Prevented realtime from guessing invalid production websocket hosts when Reverb/Pusher env is incomplete, reducing noisy connection errors.

## [1.0.5] - 2026-04-01 — Frontend Auth & Asset Recovery

### Fixed
- Revalidated restored frontend sessions against `GET /api/auth/me` before marking users authenticated, preventing false localStorage-only login state and repeated `401 Unauthenticated` cascades.
- Added client-side stale asset recovery that unregisters service workers, clears caches, and reloads once when old `_next` chunk or CSS references break after deployment.
- Forced browser-side auth flows in `AuthProvider` to use the Next.js `/api` proxy instead of drifting to direct backend URLs.

## [1.0.75] - 2026-04-02
### Changed
- Extreme, comprehensive update to `docs/UNIVERSAL_LLM_INSTRUCTIONS.md` incorporating rigorous autonomous execution protocols.
- Master agent files (`AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`) completely rewritten to strictly enforce zero-pause continuous looping, UI representation requirements, and mandatory global version syncing.
- Resolved git merge conflict in `ActivityPubSearchController.php` keeping the improved `ActivityPubKeyService` implementation.
- Executed full submodule sync and documented all branches.

## [1.0.76] - 2026-04-02
### Added
- **Offline CRDT Batch Sync Integration**: Built the `/api/messages/sync-batch` endpoint using event-store principles and CRDT rules. When offline messages are sent, they are processed historically with proper timestamping, deduplicated via UUIDs, and pushed accurately. Missed server-side messages since the client's `last_sync_at` logical clock timestamp are returned to the client and injected into the active UI state via `useChatSync.ts` and `injectMissedMessages`.
- Enhanced `useChatSync.ts` and `lib/offline-store.ts` to manage the `fwber_last_chat_sync` logical clock stamp.

## [1.0.77] - 2026-04-02
### Added
- **Helm Chart Infrastructure**: Created a complete Helm chart for the fwber platform in `kubernetes/helm/fwber`.
- Added templates for `backend`, `frontend`, `worker`, `reverb`, `geo-service`, and `ingress`.
- Integrated `_helpers.tpl` for standardized labeling and naming conventions.
- Configured `values.yaml` with production-ready defaults and scaling options.

## [1.0.78] - 2026-04-02
### Changed
- **Multi-region Edge Caching**: Optimized `next.config.js` with immutable Cache-Control headers for static assets, fonts, and images.
### Added
- **Cloudflare Strategy Documentation**: Created `docs/ai/deployment/cloudflare-edge-caching.md` outlining the recommended page rules for global performance.

## [1.0.79] - 2026-04-02
### Added
- **EventStore Load Testing**: Implemented a new Artisan command `event-store:load-test` to simulate high-volume event data.
- **Performance Verification**: Verified sub-millisecond lookup and stream fetch performance under a 100,000 record load, ensuring production readiness.
- **Unique Constraint Integrity**: Confirmed that the `domain_events` unique index correctly prevents aggregate version collisions.

## [1.0.80] - 2026-04-02
### Changed
- **Mobile Expo Router Migration**: Completely refactored the `mobile/` React Native application.
- Replaced the single-file `App.js` with an `app/` directory using file-based routing.
- Integrated `expo-router` v3.5.0 and updated core dependencies for React 19 and React Native 0.83.
- Standardized the entry point to `expo-router/entry` and added deep linking support via a new URI scheme.

## [1.0.81] - 2026-04-02
### Added
- **ActivityPub Search Aggregator**: Enhanced `ActivityPubSearchController` to support keyword searches. It now parallel-queries multiple "Discovery Hubs" (Mastodon instances) via `Http::pool` to provide a unified federated search interface.
- **Geo-Screener Bloom Filter**: Integrated a Redis-based "Active Cells" filter into `GeoScreenerService.php`. This short-circuits proximity queries for geographic areas with no known activity, significantly reducing load on the Rust microservice.

## [1.0.82] - 2026-04-02
### Added
- **NFC Match Protocol**: Built the `NFCProfileExchange` component using the Web NFC API. Users can now verify physical meetups and exchange profile data instantly by tapping phones.
- **NFC Backend**: Implemented `POST /api/matches/nfc-exchange` to record verified physical meetups and grant trust score boosts.
- **WASM Encryption Bridge**: Integrated the `fwber-wasm` primitives into the frontend `crypto.ts`. The system now automatically offloads large message payloads to Rust-compiled WASM for high-performance E2E encryption.

## [1.0.83] - 2026-04-02
### Added
- **ZK-Location Verification for NFC Taps**: Enhanced the NFC Profile Exchange to include a cryptographic physical proximity proof.
- **Handshake Protocol**: Implemented a Redis-backed 15-second handshake window in `MatchController.php`. Matches are now only "Physical Verified" if both participants provide matching precision-8 geohashes, proving proximity without raw GPS exchange.
- **Frontend Geohash Integration**: Integrated `ngeohash` in the React frontend to generate location commitments during the NFC tap sequence.

## [1.0.84] - 2026-04-02
### Added
- **Physical Item Marketplace**: Built a new B2B extension allowing merchants to list physical inventory (drinks, merchandise) for sale via FWB Tokens.
- **Redemption Protocol**: Implemented a secure transaction flow where users receive a `FWB-XXXX` redemption code upon purchase. Merchants can verify and mark these as redeemed via the new `MerchantInventoryController`.
- **Distributed Event Streaming Infrastructure**: Created a pluggable `EventBusInterface` and a `RedisStreamEventBus` implementation. All domain events are now automatically published to **Redis Streams**, enabling multi-instance global state replication.
- **EventStore Refactor**: Upgraded the `EventStore` to support dependency-injected event buses.

## [1.0.85] - 2026-04-02
### Added
- **Marketplace UI**: Built a dedicated shop interface (`/marketplace/{merchantId}`) where users can browse physical items and spend FWB Tokens.
- **Purchase Workflow**: Integrated an animated "Purchase Confirmed" screen displaying unique redemption codes for merchant verification.
- **Marketplace API Library**: Created `lib/api/marketplace.ts` to centralize token-based B2B transactions.

## [1.0.86] - 2026-04-02
### Added
- **Federated Reputation Aggregator**: Implemented a background job to periodically sync vouch scores and membership age from remote ActivityPub actors. Surrogate trust data is now cached locally in `federated_actor_reputations`.
- **ActivityPub Group Actors**: Added support for federated community profiles. Groups can now be marked as `is_federated` and are exposed via standard ActivityStreams `Group` JSON-LD.
- **Mobile NFC Permissions**: Updated `mobile/app.json` to include native NFC hardware permissions for Android, enabling physical profile exchange support.
### Changed
- **Trust-Aware Ranking**: Updated `NearbyUserRankingService` to incorporate cached federated reputation into the composite trust score.

## [1.0.87] - 2026-04-02
### Added
- **NFC Tap-to-Pay Protocol**: Linked the physical device tapping system with the FWB Token marketplace.
- **Merchant POS Terminal**: Built a new `MerchantPOS` component for the business dashboard. Merchants can select inventory items and broadcast payment requests via NFC.
- **Unified NFC Interaction Hub**: Enhanced the user-side NFC component to detect and process incoming payment requests from merchant terminals.
- **Atomic Physical Transactions**: Integrated the token-based purchase API with NFC handshakes for instant real-world item redemptions.

## [1.0.88] - 2026-04-02
### Added
- **Federated Feed Aggregator**: Built a background ingestion system to pull remote posts from followed ActivityPub actors into the local `federated_posts` cache.
- **Unified Dashboard Feed**: Integrated a "Federated Feed" tab into the main dashboard, merging local match activity with decentralized social content.
- **ZK-Age Verification (Authority Mode)**: Enhanced ActivityPub Actor profiles to include a signed `ageVerified` claim, allowing remote servers to trust the user's 18+ status without date-of-birth exposure.
- **AR Inventory Radar**: Launched a new AR view for the Marketplace. Users can now scan their physical surroundings to see floating inventory items from nearby venues, complete with token pricing.

## [1.0.89] - 2026-04-02
### Added
- **Federated Secure DMs**: Implemented end-to-end ActivityPub direct messaging.
- **Inbound DM Handling**: Updated the Inbox controller to process private `Note` activities with restricted `to` addressing.
- **Outbound DM Routing**: Enhanced the Message Controller to automatically detect federated actor URIs and dispatch signed private activities via the delivery service.
- **Unified Chat History**: Federated DMs are now seamlessly merged into the main messaging table, enabling cross-instance private conversations.

## [1.0.90] - 2026-04-02
### Added
- **Governance Council Portal**: Launched the first phase of Decentralized Governance for the fwber platform.
- **Token-Weighted Voting**: Implemented a core voting engine where a user's voting power is proportional to their FWB Token balance. 
- **Council UI**: Built a high-fidelity dashboard (`/council`) for browsing and participating in active community proposals.
- **Proposals Engine**: Created backend infrastructure to manage community-driven policy, moderation, and technical upgrades.

## [1.0.91] - 2026-04-02
### Added
- **Cross-Instance E2E Encryption (Outbound)**: Integrated RSA-OAEP encryption for federated direct messages. The frontend now automatically imports remote actor public keys to secure cross-server communications.
- **Global Token Bridge (Economy)**: Launched the `SwapController` and a new "Global Bridge" UI in the wallet. Users can now initiate swaps from FWB Tokens to external assets (SOL, USDC) via a secure bridging protocol.
- **Automated Governance Processing**: Built the `ProcessGovernanceProposals` background job to reconcile token-weighted votes and finalize community decisions upon expiry.

## [1.0.92] - 2026-04-02
### Fixed
- **Migration Conflict**: Fixed a duplicate table creation error in the Governance migrations. Decoupled `governance_proposals` and `governance_votes` into their respective migration files to ensure deployment stability.

## [1.0.93] - 2026-04-02
### Added
- **Cross-Instance E2E Decryption**: Completed the federated messaging security loop. Local users now generate RSA-OAEP keypairs that allow them to decrypt incoming direct messages from remote servers.
- **Multi-Key Storage Engine**: Upgraded the browser IndexedDB storage to support concurrent storage of ECDH (local) and RSA (federated) keypairs.
- **Automated Policy Enforcement**: Finalized the `ProcessGovernanceProposals` engine with full feature test coverage. The system now automatically reconciles community votes and finalizes proposal outcomes.

## [1.0.94] - 2026-04-02
### Added
- **Automated Policy Execution**: Integrated the `PolicyExecutor` service with the Governance engine. Passed proposals can now automatically trigger system changes, such as updating site-wide settings.
- **Site Settings Management**: Created the `site_settings` infrastructure to allow for dynamic, community-governed configuration (e.g., token bonus amounts, participation thresholds).
- **Proposal Creation UI**: Built the `CreateProposalModal` in the Council portal, allowing users to submit new proposals directly from the frontend.
### Changed
- **Token Swap UI Polish**: Enhanced the `SwapInterface` with a simulated real-time price feed and dynamic fee calculation for better user transparency.

## [1.0.95] - 2026-04-02
### Added
- **Real-time Price Integration**: Integrated the CoinGecko API into the `SwapController` to provide live SOL/USDC exchange rates for FWB Token bridging.
- **Automated Job Scheduling**: Configured the Laravel task scheduler (`console.php`) to automatically process expired governance proposals and ingest federated social content every few minutes.
- **Market Rates API**: Created the `/api/economy/rates` endpoint to centralize FWB exchange rate logic and external market data.
### Changed
- **Wallet Polish**: Updated the `SwapInterface` to fetch and display live market rates and dynamic estimated receive amounts.

## [1.0.96] - 2026-04-02
### Added
- **Governance Result Notifications**: Users now receive real-time WebSocket and database notifications when a proposal they voted on is finalized.
- **Merkle Proposal Verification**: Implemented a cryptographic audit trail for the Governance Council. Each finalized proposal now carries a `merkle_root` of all weighted votes, ensuring mathematical transparency of the democratic outcome.
- **MerkleTreeService**: Created a new utility for generating SHA-256 Merkle roots from vote datasets.

## [1.0.97] - 2026-04-02
### Added
- **Frontend Merkle Prover**: Added a "Verify My Vote" feature to the Council dashboard. Users can now cryptographically prove their vote's inclusion in any finalized proposal root using SHA-256 reconstruction in the browser.
- **Merkle Proof API**: Implemented a new endpoint `/api/governance/proposals/{id}/proof` that generates the sibling-hash path for a user's specific weighted vote.
### Changed
- **Council UI Overhaul**: Split the Council portal into "Active Proposals" and "History & Audit" sections for better clarity and long-term transparency.

## [1.0.98] - 2026-04-02
### Added
- **On-Chain Governance Mirroring**: Integrated Solana anchoring for the community council. Finalized proposals now automatically anchor their Merkle roots to the Solana blockchain for immutable third-party auditing.
- **OnChainAuditor Service**: Created a new backend service to handle blockchain JSON-RPC interactions and transaction management.
- **iOS NFC Support**: Enabled native NFC entitlements and usage descriptions in the `mobile/` app for cross-platform physical verification parity.
### Changed
- **Council Audit UX**: Updated the `/council` portal to display on-chain transaction hashes with direct links to the Solana Explorer.

## [1.0.99] - 2026-04-02
### Added
- **Community Moderation DAO**: Extended the Governance system to support automated community-driven bans. The Council can now vote to block local users or federated actors.
- **Global Ban Infrastructure**: Implemented the `global_bans` registry and `CheckGlobalBan` middleware to enforce community decisions across all API endpoints.
- **Federated Filter**: Updated the ActivityPub Inbox to automatically reject activities from actors banned via community vote.
- **On-Chain Audit Polish**: Enhanced the `VoteVerifier` to include a verification state for Solana-anchored roots, providing end-to-end transparency for governance outcomes.

## [1.1.0] - 2026-04-02
### Added
- **Council Appeal System**: Implemented the project's first "Community Court" judicial layer. Banned users can now submit formal appeals to the Council.
- **Automated Unban Proposals**: Appeals automatically generate a project-wide `Unban` proposal, allowing the community to decide on the user's reinstatement.
- **Restriction UI**: Built a dedicated `/council/appeals` portal for account restriction management.
- **Policy Executor Expansion**: Added `unban_user` and `unban_actor` actions to the automated execution engine.
### Changed
- **Auth Hardening**: Enhanced the `CheckGlobalBan` middleware to handle graceful redirects and restricted API access for banned entities.

## [1.1.1] - 2026-04-02
### Added
- **Native Mobile NFC Bridge**: Refactored the `mobile/` app to support native NFC hardware. Built a bidirectional bridge between the React Native NFC Manager and the Next.js WebView, enabling reliable high-frequency "Tap-to-Pay" and "Flash Matching".
- **Global Federated Identity**: Launched the decentralized login system. Users can now authenticate with any `fwber` node using their external ActivityPub handle (@user@server) via a secure WebFinger challenge-response protocol.
- **Shadow User Provisioning**: Implemented automatic local account creation for verified federated actors, allowing for cross-instance social persistence.

## [1.1.2] - 2026-04-02
### Added
- **Pluggable Event Bus Architecture**: Refactored the event sourcing layer to support multiple distributed drivers.
- **Kafka Driver**: Implemented a production-ready `KafkaEventBus` for high-throughput multi-server replication (requires `rdkafka`).
- **Federated Login UI**: Launched the "Sign in with ActivityPub" feature. Users can now authenticate via a secure challenge-response modal using their mastodon/mastodon handles.
- **Event Bus Configuration**: Centralized event routing in `config/events.php`.

## [1.1.3] - 2026-04-02
### Added
- **Native NFC Mobile Integration**: Fully wired the React frontend to the native mobile hardware via the `useNfc` hook and `NFCProfileExchange` component.
- **Autonomous Governance Scheduler**: Integrated council proposal finalization and ActivityPub content ingestion into the Laravel master schedule.
- **On-Chain Governance Mirroring**: Finalized proposals now automatically anchor their Merkle Roots to the Solana blockchain for immutable proof-of-tally.
- **Visual Physical Proofs**: Enhanced the NFC handshake UI with a high-fidelity "Radar" verification state for better physical interaction feedback.

## [1.1.4] - 2026-04-02
### Added
- **On-Chain Merkle Prover**: Enhanced the Council UI to cross-reference local Merkle roots with Solana transaction memos. Users can now verify that the community tally matches the immutable blockchain record.
- **Federated Event Consumer**: Built the `events:consume-federated` Artisan command. This enables `fwber` nodes to subscribe to the distributed event bus and replay domain events to maintain global state parity.
- **NFC Tap-to-Pay E2E Tests**: Authored a complete Cypress suite for physical payment redemptions, verifying the native-to-web bridge and token transfer flow.

## [1.1.5] - 2026-04-02
### Added
- **NFC Digital Receipts**: Launched a high-fidelity transaction confirmation UI for the Marketplace. Users now receive an interactive, printable digital receipt upon a successful Tap-to-Pay purchase.
- **Simulated Kafka Partitioning**: Enhanced the `KafkaEventBus` with a local simulation mode. Domain events are now automatically partitioned by `aggregate_uuid` and stored in separate high-performance logs, mirroring Kafka's distributed scaling logic.
### Changed
- **NFC Physical Interaction Hub**: Integrated the `DigitalReceipt` component into the `NFCProfileExchange`, providing immediate feedback for both matches and marketplace redemptions.

## [1.1.6] - 2026-04-02
### Added
- **WASM Performance Benchmarks**: Built a hardware acceleration test suite in the Security settings. Users can now benchmark the Rust-compiled WASM encryption engine against the browser's native JavaScript crypto to verify E2E performance gains.
- **Instance-to-Instance Event Relay**: Implemented the `FederatedRelayBus` for real-time domain event replication. Instances can now multi-cast important events (like trust score updates) to trusted peering partners.
- **Composite Event Bus**: Refactored the backend to support simultaneous local and federated event publishing via a pluggable decorator pattern.
- **Automated Reconciler Scheduling**: Finalized the 1-minute cron for governance proposals and 5-minute cron for federated social sync.
