# TODO — fwber Short-Term Task List

> **Version:** 0.3.37  
> **Last Updated:** 2026-02-26  
> **Priority Legend:** 🔴 Critical | 🟡 High | 🟢 Medium | ⚪ Low

---

## ~~🔴 Critical: Version & Build Hygiene~~ ✅ RESOLVED

- [x] **Dynamic Version Display**: `app/layout.tsx` now reads `process.env.NEXT_PUBLIC_PROJECT_VERSION` injected from `VERSION` file via `next.config.js`.
- [x] **Version Sync**: All files synchronized to 0.3.37: `VERSION`, `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`, `layout.tsx`.

## ~~🟡 High: Commented-Out Code & Technical Debt~~ ✅ RESOLVED

All previously commented-out routes and feature flags have been verified as already enabled/uncommented in prior sessions. The `MISSING_FEATURES.md` references were stale.

## 🟡 High: Feature Flags to Enable

The following features are disabled by default in `config/features.php` and should be enabled for production:

- [ ] `chatrooms` — Proximity chatrooms
- [ ] `proximity_chatrooms` — Location-gated groups
- [ ] `recommendations` — AI "For You" feed
- [ ] `websocket` — Real-time features (Reverb/Pusher)
- [ ] `content_generation` — AI profile generation
- [ ] `analytics` — User analytics
- [ ] `face_reveal` — Progressive photo reveal
- [ ] `local_media_vault` — Local media storage
- [ ] `moderation` — Auto-moderation
- [ ] `media_analysis` — AI content scanning
- [ ] `ai_wingman` — Chat suggestions
- [ ] `video_chat` — WebRTC video

## 🟡 High: Mock Implementations to Replace

| Service | Current State | Target |
|---------|--------------|--------|
| `PrivacySecurityService.php` | Mock endpoints | Real AWS Rekognition/Google Vision |
| `AIMatchingService.php` | SQL keyword matching | Vector Embeddings (Phase 6) |

## 🟢 Medium: UI Polish & Missing Representations

- [ ] **Tier Unlock Guide**: Verify animations render correctly on mobile viewports.
- [ ] **Conversation Coach**: Verify real-time feedback displays correctly in all chat views.
- [ ] **Notification Bell**: Ensure all notification types (gift, match, message, event) have distinct icons and correct deep links.
- [ ] **Settings Page**: Audit all settings toggles to ensure they persist via the API and are reflected in the UI.
- [ ] **Admin Dashboard**: Wire up the remaining admin analytics charts (if any are showing placeholder data).

## 🟢 Medium: Documentation Gaps

- [ ] **DEPLOY.md**: Create/update with latest Docker + Kubernetes + DreamHost deployment instructions.
- [ ] **API Documentation**: Regenerate `api-docs.json` via `l5-swagger:generate` to include all new endpoints.
- [ ] **User Manual / Help Center**: Audit `/help` page for completeness against all implemented features.

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
