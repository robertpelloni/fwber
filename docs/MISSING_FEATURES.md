# Missing Features Inventory

**Generated:** January 9, 2026  
**Purpose:** Document backend features that exist but lack frontend UI implementation

This document catalogs features discovered during exhaustive codebase analysis that have complete or partial backend implementations but are missing frontend user interfaces.

---

## Critical Priority (High User Impact)

### 1. Achievements System 🎯
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `achievements`, `user_achievements`, `achievement_categories` tables
- Models: Achievement, UserAchievement with relationships
- Logic: Criteria-based unlocking, token rewards, categories (social, growth, activity)

**What's Missing:**
- Achievements page/tab in user profile or dashboard
- Achievement unlock notifications
- Progress tracking UI
- Badge display on profiles

**User Impact:** Users can't see or track achievements, missing gamification engagement and token earning opportunities.

**Files:**
- `fwber-backend/database/migrations/2025_12_15_051734_create_achievements_tables.php`
- `fwber-backend/app/Models/Achievement.php`

---

### 2. Proximity Chatrooms 🗣️
**Backend Status:** Complete  
**Frontend Status:** Partial (API exists, limited UI)

**What Exists:**
- Database: `proximity_chatrooms`, `proximity_chatroom_members`, `proximity_chatroom_messages`
- Controllers: `ProximityChatroomController` with full CRUD
- Features: Geolocation-based, networking/social modes, member management, reactions, mentions

**What's Missing:**
- Chatroom discovery/browsing interface
- Create chatroom flow
- Join nearby chatrooms map view
- Message reactions UI (placeholder exists)
- Member management UI

**User Impact:** Core social feature is largely inaccessible despite full backend.

**Files:**
- `fwber-backend/app/Http/Controllers/ProximityChatroomController.php`
- `fwber-frontend/app/chatrooms/[id]/page.tsx` (basic view only)

---

### 3. Bulletin Boards 📋
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `bulletin_boards`, `bulletin_board_posts`, `bulletin_board_subscriptions`
- Controller: `BulletinBoardController` with full API
- Features: Location-based boards, threaded messages, subscriptions

**What's Missing:**
- Bulletin board discovery page
- Board creation interface
- Post/reply UI
- Subscription management

**User Impact:** Community discussion feature is completely unused.

**Files:**
- `fwber-backend/app/Http/Controllers/BulletinBoardController.php`
- Routes commented out in `api.php` (lines 443-444)

---

### 4. Profile View Tracking 👀
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `profile_views` table with viewer tracking
- Logic: Anonymous/visible view modes, view counts

**What's Missing:**
- "Who viewed your profile" page
- View count display on profile
- Viewer list (for premium users)

**User Impact:** Social discovery feature hidden from users.

**Files:**
- `fwber-backend/database/migrations/2025_12_13_063624_create_profile_views_table.php`

---

## High Priority (Revenue Impact)

### 5. Paid Photo Reveals 📸
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `photo_unlocks` table
- Logic: Token-based photo unlock system
- API: Unlock endpoints exist

**What's Missing:**
- Blurred photo preview with unlock button
- Token payment flow for photos
- Unlock history

**User Impact:** Monetization feature unavailable to users.

**Files:**
- `fwber-backend/database/migrations/2025_12_14_000000_create_photo_unlocks_table.php`

---

### 6. Content Unlock System 🔓
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `content_unlocks` table
- Logic: Generic framework for premium content
- Types: Can unlock various content types with tokens

**What's Missing:**
- Content gate UI components
- Unlock purchase flow
- Unlocked content library

**User Impact:** Flexible monetization system entirely unused.

**Files:**
- `fwber-backend/database/migrations/2025_12_23_174829_create_content_unlocks_table.php`

---

### 7. Merchant Promotions Discovery 🏪
**Backend Status:** Complete  
**Frontend Status:** Partial

**What Exists:**
- Database: `merchant_profiles`, `promotions` tables
- Controllers: Full merchant and promotion management
- Features: Geolocated promotions, discount codes, payment tracking
- UI: Basic merchant dashboard, "Sponsored" styling in Local Pulse

**What's Missing:**
- User-facing promotion discovery/browsing
- Nearby deals map
- Promotion redemption flow
- Merchant search

**User Impact:** Local commerce feature unusable for consumers.

**Files:**
- `fwber-backend/app/Http/Controllers/MerchantController.php`
- `fwber-backend/database/migrations/2026_01_06_042019_create_merchant_profiles_table.php`

---

## Medium Priority (Engagement Impact)

### 8. Share-to-Unlock 🔗
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `share_unlocks` table
- Logic: Track shares, view counts, unlock thresholds
- Documented in `docs/features/VIRAL_GROWTH.md`

**What's Missing:**
- Share buttons with unlock incentives
- Progress tracking ("3 more shares to unlock X")
- Social platform integrations

**User Impact:** Viral growth feature not implemented.

**Files:**
- `fwber-backend/database/migrations/2025_12_13_035232_create_share_unlocks_table.php`
- `docs/features/VIRAL_GROWTH.md`

---

### 9. Message Reactions 💬
**Backend Status:** Partial  
**Frontend Status:** Placeholder

**What Exists:**
- Database: `chatroom_message_mentions` table
- Frontend: Placeholder buttons in chatroom UI

**What's Missing:**
- Reaction picker UI
- Reaction display on messages
- Reaction notification

**User Impact:** Modern messaging feature missing.

**Files:**
- `fwber-frontend/app/chatrooms/[id]/page.tsx` (comment: "This would need to be implemented")
- `fwber-frontend/components/chatrooms/Chatroom.tsx`

---

### 10. Group-to-Group Matching 👥
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `group_matches` table
- Service: `GroupMatchingService` with matching logic
- API: Group match endpoints

**What's Missing:**
- Group matching discovery page
- Match request/accept flow
- Group compatibility display

**User Impact:** Group social dynamics not implemented.

**Files:**
- `fwber-backend/database/migrations/2025_12_31_182517_create_group_matches_table.php`
- `fwber-backend/app/Services/GroupMatchingService.php`

---

### 11. Matchmaker Bounties 💰
**Backend Status:** Complete  
**Frontend Status:** Basic

**What Exists:**
- Database: `match_bounties`, `match_assists` tables
- Controller: `MatchBountyController`
- Logic: Token rewards for successful matches

**What's Missing:**
- Bounty browsing/discovery
- Friend suggestion interface
- Bounty claim flow
- Success tracking

**User Impact:** Social matching feature underutilized.

**Files:**
- `fwber-backend/database/migrations/2025_12_29_000000_create_match_bounties_table.php`

---

### 12. E2E Encryption 🔐
**Backend Status:** Infrastructure exists  
**Frontend Status:** No UI

**What Exists:**
- Database: `user_public_keys` table
- Note: "For multi-device support (future)"

**What's Missing:**
- Key generation/management UI
- Encrypted messaging toggle
- Key verification flow

**User Impact:** Privacy feature not user-accessible.

**Files:**
- `fwber-backend/database/migrations/2025_12_13_181619_create_user_public_keys_table.php`

---

### 13. Extended Viral Content 🤖
**Backend Status:** Complete  
**Frontend Status:** Partial (only roasts)

**What Exists:**
- Database: `viral_contents` table
- Types: roasts, fortunes, cosmic matches, nemesis finder
- AI generation logic for all types

**What's Missing:**
- Fortune generation UI
- Cosmic match UI
- Nemesis finder UI
- Content sharing for non-roast types

**User Impact:** Rich AI content features hidden.

**Files:**
- `fwber-backend/database/migrations/2025_12_15_121931_create_viral_contents_table.php`

---

## Low Priority (Nice to Have)

### 14. Group Events 🎭
**Backend Status:** Complete  
**Frontend Status:** No UI

**What Exists:**
- Database: `event_groups` table linking events to groups

**What's Missing:**
- Group event creation flow
- Group event discovery
- Shared event management

**Files:**
- `fwber-backend/database/migrations/2025_12_31_190319_create_event_groups_table.php`

---

### 15. Daily Active Users Analytics 📊
**Backend Status:** Complete  
**Frontend Status:** Admin only

**What Exists:**
- Database: `daily_active_users` table
- Tracking logic in backend

**What's Missing:**
- User-facing engagement metrics
- Personal analytics dashboard

**Files:**
- `fwber-backend/database/migrations/2025_12_13_090608_create_daily_active_users_table.php`

---

### 16. Gift System Enhancement 🎁
**Backend Status:** Complete  
**Frontend Status:** Basic

**What Exists:**
- Database: `user_gifts` table
- Gift shop modal exists

**What's Missing:**
- Gift animations
- Gift history page
- Custom gift messages display

**Files:**
- `fwber-backend/database/migrations/2025_12_12_140245_create_user_gifts_table.php`

---

## Technical Debt

### Commented-Out Code Requiring Attention

| File | Issue |
|------|-------|
| `routes/api.php` | WebSocket routes commented out (lines 192-194) |
| `routes/api.php` | Bulletin board subscription routes commented out |
| `ApmMiddleware.php` | APM tracking commented out |
| `MediaUploadService.php` | Video thumbnail generation commented out |
| `SubscriptionController.php` | Stripe cancellation commented out |
| `MerchantController.php` | Verification check commented out |

### Mock Implementations to Replace

| Service | Current State | Needed |
|---------|--------------|--------|
| `PrivacySecurityService.php` | Mock API endpoints | Real AWS Rekognition/Google Vision |
| `OpenAIVisionDriver.php` | Mock safety scores | Real content moderation |
| `AIMatchingService.php` | Simple keyword matching | NLP-based matching |
| `ContentGenerationService.php` | Simplified personality analysis | Full AI personality profiling |
| `content-generation.ts` | Mock safety score | Real moderation API |

### Feature Flags Currently Disabled

These features are disabled by default in `config/features.php`:

- `chatrooms` - Proximity chatrooms
- `proximity_chatrooms` - Location-gated groups
- `recommendations` - AI "For You" feed
- `websocket` - Real-time features
- `content_generation` - AI profile generation
- `analytics` - User analytics
- `face_reveal` - Progressive photo reveal
- `local_media_vault` - Local media storage
- `moderation` - Auto-moderation
- `media_analysis` - AI content scanning
- `ai_wingman` - Chat suggestions
- `video_chat` - WebRTC video

---

## Implementation Priority Matrix

| Priority | Feature | Effort | Impact | Revenue |
|----------|---------|--------|--------|---------|
| 🔴 Critical | Achievements UI | Medium | High | Medium |
| 🔴 Critical | Proximity Chatrooms | High | High | Low |
| 🔴 Critical | Paid Photo Reveals | Low | Medium | High |
| 🟡 High | Share-to-Unlock | Medium | High | Low |
| 🟡 High | Merchant Discovery | Medium | Medium | High |
| 🟡 High | Message Reactions | Low | Medium | Low |
| 🟢 Medium | Bulletin Boards | Medium | Medium | Low |
| 🟢 Medium | Profile Views | Low | Medium | Medium |
| 🟢 Medium | Group Matching | Medium | Medium | Low |
| 🔵 Low | E2E Encryption | High | Low | Low |
| 🔵 Low | Extended Viral Content | Low | Medium | Low |

---

## Next Steps

1. **Enable Feature Flags** - Start with `chatrooms`, `recommendations`, `ai_wingman`
2. **Build Achievements UI** - Quick win for user engagement
3. **Implement Paid Photo Reveals** - Quick revenue enablement
4. **Add Share-to-Unlock** - Viral growth activation
5. **Complete Proximity Chatrooms** - Core social feature

---

*This document should be updated as features are implemented.*
