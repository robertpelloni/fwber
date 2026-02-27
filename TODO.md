# TODO — fwber Short-Term Task List

> **Version:** 0.3.41  
> **Last Updated:** 2026-02-26  
> **Priority Legend:** 🔴 Critical | 🟡 High | 🟢 Medium | ⚪ Low

---

## ~~🔴 Critical: Version & Build Hygiene~~ ✅ RESOLVED

- [x] **Dynamic Version Display**: `app/layout.tsx` now reads `process.env.NEXT_PUBLIC_PROJECT_VERSION` injected from `VERSION` file via `next.config.js`.
- [x] **Version Sync**: All files synchronized to 0.3.37: `VERSION`, `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`, `layout.tsx`.

## ~~🟡 High: Commented-Out Code & Technical Debt~~ ✅ RESOLVED

All previously commented-out routes and feature flags have been verified as already enabled/uncommented in prior sessions. The `MISSING_FEATURES.md` references were stale.

## ~~🟡 High: Error Handling & Security Patterns~~ ✅ RESOLVED

- [x] **API Client Error Handling**: Frontend `apiErrorHandling.ts` provides robust retry logic, exponential backoff, and strict `ApiError` normalization.
- [x] **Backend Validation & Catching**: Backend utilizes 63+ `FormRequest` instances for input validation and consistently catches `\Throwable` instead of `\Exception`.

## ~~🟡 High: Feature Flags to Enable~~ ✅ RESOLVED

All features are already correctly defaulted to `true` and use `env()` in `config/features.php`. No action required.

## ~~🟡 High: Mock Implementations to Replace~~ ✅ RESOLVED

| Service | Current State | Target |
|---------|--------------|--------|
| `PrivacySecurityService.php` | ✅ `AwsRekognitionDriver` & `GoogleVisionDriver` | Real AWS Rekognition/Google Vision |
| `AIMatchingService.php` | ✅ OpenAI `text-embedding-3-small` | Vector Embeddings (Phase 6) |

## ~~🟢 Medium: UI Polish & Missing Representations~~ ✅ RESOLVED

- [x] **Tier Unlock Guide**: Verify animations render correctly on mobile viewports.
- [x] **Conversation Coach**: Verify real-time feedback displays correctly in all chat views.
- [x] **Notification Bell**: Ensure all notification types (gift, match, message, event) have distinct icons and correct deep links.
- [x] **Settings Page**: Audit all settings toggles to ensure they persist via the API and are reflected in the UI.
- [x] **Admin Dashboard**: Wire up the remaining admin analytics charts (if any are showing placeholder data).

## ~~🟢 Medium: Documentation Gaps~~ ✅ RESOLVED

- [x] **DEPLOY.md**: Create/update with latest Docker + Kubernetes + DreamHost deployment instructions.
- [x] **API Documentation**: Regenerate `api-docs.json` via `l5-swagger:generate` to include all new endpoints.
- [x] **User Manual / Help Center**: Audit `/help` page for completeness against all implemented features.

## ⚪ Low: Future Enhancements

- [ ] **React Native Mobile App**: Wrap PWA in a native shell (Phase 6).
- [ ] **Multi-Region Deployment**: Geo-DNS + edge caching (Phase 6).
- [ ] **Voice/Audio Dating Rooms**: Clubhouse-style audio rooms (Phase 6).
- [ ] **ZK-Proximity Proofs**: Privacy-first location verification.
- [ ] **Burner Communication Bridge**: Ephemeral anonymous relay QR codes.
- [ ] **Evolving AI Avatars**: Dynamic emotional avatar expressions.
- [ ] **Rust Geo-Screener**: H3/S2 spatial indexing microservice for high-density areas.
- [ ] **Federated Servers**: ActivityPub-compatible self-hosted nodes (Phase 7).

---

*This file is maintained by all AI agents. Update it after every session.*
