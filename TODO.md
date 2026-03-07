# TODO — fwber Credibility Sprint

> **Version:** 0.5.0-beta  
> **Last Updated:** 2026-03-07  
> **Mode:** Stabilization & Launch Consolidation

---

## 🔴 Critical: Credibility Hardening (Phase 1)

- [x] Unify license (MIT) and remove AGPL artifact
- [x] Enforce `VERSION` as canonical source (0.5.0-beta)
- [x] Sync version across all agent instruction files
- [x] Publish `SECURITY.md`
- [x] Archive 33 stale handoff/status/update files
- [x] Rewrite `PROJECT_STATUS.md` to honest, concise state
- [x] Rewrite agent instructions with Stabilization Mode policy
- [x] Create `docs/FEATURE_STATUS_MATRIX.md`
- [x] Add CI drift checks for version/license/secret consistency

## 🟡 High: Core Flow Verification (Phase 2)

- [x] Verify register → login → logout works end-to-end (57 tests, 194 assertions)
- [x] Verify onboarding wizard completes successfully (OnboardingProfileUpdateTest)
- [x] Verify Local Pulse loads proximity artifacts (ProximityArtifactTest — 12 tests)
- [x] Verify match flow (discover → like → match) (MatchTest + PremiumMatchFilterTest)
- [x] Verify direct messaging (send + receive) (MessageTest)
- [x] Run backend test suite and fix any failures (285 passed, 0 failures)
- [x] Run frontend build and fix any errors (4 pre-existing errors fixed, build green)
- [x] Document verification results in `docs/RELEASE_EVIDENCE.md`

## 🟢 Medium: Launch Preparation

- [ ] Execute content strategy from `docs/marketing/CONTENT_STRATEGY.md` (647 lines ready)
- [ ] Prepare Detroit metro beta launch materials (flyers, stickers, coasters)
- [ ] Set up basic analytics (signups, DAU, matches/day) — `AnalyticsController` exists
- [x] Review production deployment setup — `deploy.sh`, `docker-compose.yml`, `DEPLOY.md`, K8s manifests all solid

## ⚪ Low: Technical Debt

- [x] Clean up `.gitignore` (133→78 lines, removed 10 duplicate `node_modules` entries)
- [x] Review feature flags in `config/features.php` — disabled 6 unverified features
- [x] Rename stale Mercure references to Reverb (8 files — coordinated backend + frontend)
- [x] Audit controllers — 89 total, 8 unused: `ConfigController`, `GroupPostController`, `PhotoRevealController`, `PhotoUnlockController`, `ProximityArtifactInteractionController`, `PushSubscriptionController`, `Schemas`, `TelemetryReportController`

---

*This file reflects real, actionable tasks. Items are only marked complete when verified with evidence.*
