# TODO — fwber Immediate Action Items

> **Version:** 1.8.11
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Restore Branch Completion
- [ ] **Re-run GitHub Backend CI on `restore/pre-simplification-hetzner`**: Confirm the avatar-generation and tagged-cache compatibility repairs eliminate the previously observed red run.
- [ ] **Continue Broad Rewind Reconciliation**: Stop doing only tiny selective restores and instead bring the restore branch the rest of the way toward the broader 2–3-day-ago surface, while still excluding federation, governance, and journals/scrapbooks/icebreakers.
- [x] **Audit Remaining Restore-Branch Runtime Drift**: Identified 'ghosted' migrations on production where the ledger says they ran but tables were missing; added a comprehensive repair migration (`v1.8.12`).
- [x] **Promote Restore Branch Once Stable**: Main mainline now fully replaced with the stable restored product surface.

## 🟡 High: Production & Deployment Verification
- [x] **Deploy NodeInfo 500 Fix to Hetzner**: Fix confirmed working live via `curl` check.
- [x] **Verify Live Frontend API Recovery**: Confirmed `api.fwber.me` responds correctly and frontend build uses correct proxy.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the backend/public-route repair deploy.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s while the restore branch is being broadened.

## ✅ Recently Completed
- [x] **Rewind Shell Product-Map Consistency**: Locked in domain-based shell organization, documented the `globalThis.Map` fix, and kept the signed-in shell aligned with the dashboard’s restored product map.
- [x] **Rewind Sidebar Domain Organization**: Reorganized the sidebar and mobile restored-surfaces navigation into domain groups so the app shell now mirrors the dashboard’s product map instead of presenting a flat feature list.
- [x] **Rewind Dashboard Domain Organization**: Reorganized the dashboard’s restored-surfaces area into product-domain sections so the restored branch feels more intentionally structured and less like a flat list of recovered routes.
- [x] **Rewind Matching Hub Recovery**: Added a new `/matching` hub and surfaced recommendations, matches, match dashboard, who-likes-you, profile-view signals, and nearby dating context from one coherent restored destination.
- [x] **Rewind Support Hub Recovery**: Added a new `/support` hub and surfaced help, support contact, privacy policy, terms, safety, and blocked-user controls from one coherent restored destination.
- [x] **Rewind Plans Hub Recovery**: Added a new `/plans` hub and surfaced events, event creation, date planning, nearby discovery, venues, and deals from one coherent restored destination.
- [x] **Rewind Commerce Hub Recovery**: Added a new `/commerce` hub and surfaced merchant onboarding, dashboard operations, merchant profile, promotions, analytics, vibe broadcasting, and adjacent business-control entry points from one coherent restored destination.
- [x] **Rewind Economy Hub Recovery**: Added a new `/economy` hub and surfaced premium, wallet, referrals, boosts, gifts, and unlock-related monetization flows from one coherent restored destination.
- [x] **Rewind Identity Hub Recovery**: Added a new `/identity` hub and surfaced profile, photos, identity settings, verification, physical-profile controls, and security/recovery access from one coherent restored destination.
- [x] **Rewind Operations Hub Recovery**: Added a new `/operations` hub and surfaced safety, settings, security, merchant flows, and moderation/travel operational tooling from one coherent restored destination.
- [x] **Rewind Connections Hub Recovery**: Added a new `/connections` hub and surfaced messages, friends, activity, notifications, matches, and groups from one coherent restored destination.
- [x] **Rewind Studio Hub Recovery**: Added a new `/studio` hub and surfaced roast, roast-date, content generation, wingman, bounties, and analytics from one coherent restored destination.
- [x] **Rewind Scenes Hub Recovery**: Added a new `/scenes` hub and surfaced recommendations, groups, topics, matches, match dashboard, and leaderboard-style discovery from one coherent restored destination.
- [x] **Rewind Reputation Hub Recovery**: Added a new `/reputation` hub and surfaced achievements, leaderboard, profile views, verification, and adjacent trust signals from one coherent restored destination.
- [x] **Rewind Places Hub + Avatar Provider Test Fallback**: Added a new `/places` hub for local-discovery surfaces and pinned implicit avatar test provider fallback to DALL-E for the richer rewind test contract.
- [x] **Rewind Avatar Prompt Interest Label Fix**: Title-cased normalized interest labels in avatar prompt generation so the restore branch better matches the richer avatar-generation test contract.
- [x] **Rewind Live-Spaces Hub Recovery**: Added a new top-level `/spaces` hub and surfaced chatrooms, audio rooms, bulletin boards, local pulse, conference pulse, burner bridge, and proximity chatrooms from one coherent restored destination.
- [x] **Rewind Avatar Prompt Relation Refresh Fix**: Patched restore-branch avatar prompt generation to query the latest profile row directly, targeting the remaining explicit avatar-generation backend CI failure.
- [x] **Rewind Unlock Hub + Paywall Surface Navigation Recovery**: Added a new top-level `/unlocks` page and surfaced premium unlocks, who-likes-you, share unlocks, and photo reveals through the dashboard + restored-features navigation.
- [x] **Rewind Surface Recovery for Boosts, Gifts, Referrals, and Video**: Added dedicated top-level pages for these restored systems and surfaced them in the dashboard + restored-features navigation so the richer branch exposes more of the approved token-era product again.
- [x] **Rewind CI Repair for Avatar Requests + Recommendation Caching**: Patched restore-branch avatar generation to preserve outbound HTTP fake assertions in tests and restored tagged caching in `RecommendationController`, directly targeting the next backend CI blockers.
- [x] **Rewind Navigation Recovery + Missing Activity Surfaces**: Reworked the restore-branch shell to spotlight approved restored areas, added real `/activity` and `/notifications` pages, added shared notification route helpers, and rebuilt the dashboard around visible restored surfaces; validated with a successful frontend production build.
- [x] **Restore Branch CI Compatibility Sweep**: Repaired avatar-generation config/prompt compatibility, tagged-cache mock behavior, frontend Sentry App Router instrumentation, broken WASM import assumptions, and stale Sentry webpack options in the rewind branch; validated with `php artisan test` (**425 passed / 8 skipped**) and successful frontend build.
- [x] **NodeInfo 500 Recovery + Frontend CI Runtime Fix**: Hardened discovery routes against missing federation columns and aligned the frontend GitHub workflow to Node.js 24.
- [x] **Hetzner Log ACL Deploy Fix**: Removed the broken log permission override, switched to ACL-based shared log access, and repaired the live Hetzner server log directory ACLs.
- [x] **Hetzner Backend Stability Repair**: Replaced the broken root route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
