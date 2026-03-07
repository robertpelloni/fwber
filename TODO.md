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

- [ ] Execute content strategy from `docs/marketing/CONTENT_STRATEGY.md`
- [ ] Prepare Detroit metro beta launch materials
- [ ] Set up basic analytics (signups, DAU, matches/day)
- [ ] Review and test production deployment script

## ⚪ Low: Technical Debt

- [ ] Clean up duplicate `node_modules` entries in `.gitignore`
- [ ] Remove stale references to Mercure (migrated to Reverb)
- [ ] Audit 78 controllers — identify unused ones
- [ ] Review feature flags in `config/features.php` — disable unverified features

---

*This file reflects real, actionable tasks. Items are only marked complete when verified with evidence.*
