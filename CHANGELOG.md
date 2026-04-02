# Changelog

All notable changes to this project will be documented in this file.

## [1.0.51] - 2026-04-02 — Trust-Aware Local Pulse Ranking

### Fixed
- Reworked Local Pulse ordering so nearby artifacts are ranked by a privacy-safe composite of trusted connections, scene alignment, and freshness instead of pure recency.
- Added `LocalPulseRankingService` to batch friendship, confirmed relationship-link, and shared-circle checks into an internal trust map without exposing private graph edges in the API payload.
- Updated Local Pulse response metadata and UI copy so the feed now explains that ranking uses scene alignment, trusted connections, and freshness while keeping trust details internal to ordering.
- Added regression coverage proving a trusted, scene-aligned Local Pulse artifact outranks a newer generic stranger post.

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
