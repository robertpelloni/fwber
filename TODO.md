# TODO — fwber Short-Term Task List

> **Version:** 0.3.37  
> **Last Updated:** 2026-02-26  
> **Priority Legend:** 🔴 Critical | 🟡 High | 🟢 Medium | ⚪ Low

---

## 🔴 Critical: Version & Build Hygiene

- [ ] **Dynamic Version Display**: `app/layout.tsx` line 120 hardcodes `v0.3.2`. Replace with a dynamic read from the `VERSION` file or `process.env.NEXT_PUBLIC_APP_VERSION` injected at build time.
- [ ] **Version Sync**: Synchronize version numbers across `VERSION`, `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `GPT.md`, `copilot-instructions.md`, `package.json`, and `layout.tsx`.

## 🟡 High: Commented-Out Code & Technical Debt

- [ ] **Uncomment Bulletin Board Routes**: `routes/api.php` has bulletin board subscription routes commented out.
- [ ] **Uncomment WebSocket Routes**: `routes/api.php` lines 192-194 have WebSocket routes commented out.
- [ ] **Re-enable APM Middleware**: `ApmMiddleware.php` has APM tracking commented out — re-enable or make it config-driven.
- [ ] **Re-enable Video Thumbnails**: `MediaUploadService.php` has video thumbnail generation commented out — re-enable with FFMpeg fallback.
- [ ] **Re-enable Stripe Cancellation**: `SubscriptionController.php` has Stripe cancellation commented out.
- [ ] **Re-enable Merchant Verification**: `MerchantController.php` has verification check commented out.

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
