# TODO — fwber Immediate Action Items

> **Version:** 1.7.2
> **Last Updated:** 2026-04-05

---

## 🔴 Critical: Restore Branch Completion
- [ ] **Re-run GitHub Backend CI on `restore/pre-simplification-hetzner`**: Confirm the avatar-generation and tagged-cache compatibility repairs eliminate the previously observed red run.
- [ ] **Continue Broad Rewind Reconciliation**: Stop doing only tiny selective restores and instead bring the restore branch the rest of the way toward the broader 2–3-day-ago surface, while still excluding federation, governance, and journals/scrapbooks/icebreakers.
- [ ] **Audit Remaining Restore-Branch Runtime Drift**: Sweep for other old-snapshot assumptions similar to Sentry/WASM/tagged-cache/config drift.
- [ ] **Promote Restore Branch Once Stable**: When backend CI and frontend build are consistently green, prepare the restore branch to supersede one-off incremental restoration work.

## 🟡 High: Production & Deployment Verification
- [ ] **Deploy NodeInfo 500 Fix to Hetzner**: Push the `NodeInfoController` guard through the Hetzner deploy path and confirm `/nodeinfo/2.0` stops 500ing live.
- [ ] **Verify Live Frontend API Recovery**: Confirm the dashboard and E2E restore calls now hit `api.fwber.me` instead of `www.fwber.me/api/*` after Vercel finishes deploying the updated frontend.
- [ ] **Repair / Confirm Realtime Contract**: Re-check the live header connection badge and websocket/broadcast-auth behavior after the backend/public-route repair deploy.
- [ ] **Production 500 Error Sweep**: Continue collecting and eliminating real production 500s while the restore branch is being broadened.

## ✅ Recently Completed
- [x] **Rewind Surface Recovery for Boosts, Gifts, Referrals, and Video**: Added dedicated top-level pages for these restored systems and surfaced them in the dashboard + restored-features navigation so the richer branch exposes more of the approved token-era product again.
- [x] **Rewind CI Repair for Avatar Requests + Recommendation Caching**: Patched restore-branch avatar generation to preserve outbound HTTP fake assertions in tests and restored tagged caching in `RecommendationController`, directly targeting the next backend CI blockers.
- [x] **Rewind Navigation Recovery + Missing Activity Surfaces**: Reworked the restore-branch shell to spotlight approved restored areas, added real `/activity` and `/notifications` pages, added shared notification route helpers, and rebuilt the dashboard around visible restored surfaces; validated with a successful frontend production build.
- [x] **Restore Branch CI Compatibility Sweep**: Repaired avatar-generation config/prompt compatibility, tagged-cache mock behavior, frontend Sentry App Router instrumentation, broken WASM import assumptions, and stale Sentry webpack options in the rewind branch; validated with `php artisan test` (**425 passed / 8 skipped**) and successful frontend build.
- [x] **NodeInfo 500 Recovery + Frontend CI Runtime Fix**: Hardened discovery routes against missing federation columns and aligned the frontend GitHub workflow to Node.js 24.
- [x] **Hetzner Log ACL Deploy Fix**: Removed the broken log permission override, switched to ACL-based shared log access, and repaired the live Hetzner server log directory ACLs.
- [x] **Hetzner Backend Stability Repair**: Replaced the broken root route, restored the missing `WebFingerController`, hardened dashboard endpoints against missing `user_matches`, fixed the PHP 8.4 dashboard `limit` type bug, and added a corrective migration for drifted match tables.

---
*This file is continuously updated by autonomous AI agents. Do not leave items unchecked if they are completed.*
