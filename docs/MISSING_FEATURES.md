# Missing Features Inventory

**Generated:** January 9, 2026  
**Last Updated:** January 10, 2026  
**Purpose:** Document backend features that exist but lack frontend UI implementation

This document catalogs features discovered during exhaustive codebase analysis that have complete or partial backend implementations but are missing frontend user interfaces.

---

## ✅ Recently Completed (Phase 4B - Jan 2026)

### 1. Achievements System 🎯 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented

**What Exists:**
- Database: `achievements`, `user_achievements`, `achievement_categories` tables
- Models: Achievement, UserAchievement with relationships
- Logic: Criteria-based unlocking, token rewards, categories (social, growth, activity)
- ✅ UI: `fwber-frontend/app/achievements/page.tsx` (364 lines, full implementation)
  - Achievement categories with progress tracking
  - Badge display with unlock status
  - Token rewards display

**Files:**
- `fwber-backend/database/migrations/2025_12_15_051734_create_achievements_tables.php`
- `fwber-backend/app/Models/Achievement.php`
- `fwber-frontend/app/achievements/page.tsx` ✅

---

### 2. Proximity Chatrooms 🗣️ ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented

**What Exists:**
- Database: `proximity_chatrooms`, `proximity_chatroom_members`, `proximity_chatroom_messages`
- Controllers: `ProximityChatroomController` with full CRUD
- Features: Geolocation-based, networking/social modes, member management, reactions, mentions
- ✅ UI: `fwber-frontend/app/proximity-chatrooms/page.tsx` (411 lines)
  - Chatroom discovery/browsing interface
  - Create chatroom flow with location selection
  - Join nearby chatrooms
  - Member count and activity display

**Files:**
- `fwber-backend/app/Http/Controllers/ProximityChatroomController.php`
- `fwber-frontend/app/proximity-chatrooms/page.tsx` ✅
- `fwber-frontend/app/chatrooms/[id]/page.tsx`

---

### 3. Bulletin Boards 📋 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented

**What Exists:**
- Database: `bulletin_boards`, `bulletin_board_posts`, `bulletin_board_subscriptions`
- Controller: `BulletinBoardController` with full API
- Features: Location-based boards, threaded messages, subscriptions
- ✅ UI: `fwber-frontend/app/bulletin-boards/page.tsx`

**Note:** Routes may still be commented out in `api.php` (lines 443-444) - needs enabling.

**Files:**
- `fwber-backend/app/Http/Controllers/BulletinBoardController.php`
- `fwber-frontend/app/bulletin-boards/page.tsx` ✅

---

### 4. Profile View Tracking 👀 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented

**What Exists:**
- Database: `profile_views` table with viewer tracking
- Logic: Anonymous/visible view modes, view counts
- ✅ UI: `fwber-frontend/app/profile-views/page.tsx` (290 lines)
  - "Who viewed your profile" page
  - View count statistics
  - Viewer list with timestamps

**Files:**
- `fwber-backend/database/migrations/2025_12_13_063624_create_profile_views_table.php`
- `fwber-frontend/app/profile-views/page.tsx` ✅

---

### 5. Paid Photo Reveals 📸 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented (v0.3.28)

**What Exists:**
- Database: `photo_unlocks` table
- Logic: Token-based photo unlock system
- API: Unlock endpoints exist
- ✅ UI: `fwber-frontend/app/photos/reveals/page.tsx`

**Files:**
- `fwber-backend/database/migrations/2025_12_14_000000_create_photo_unlocks_table.php`
- `fwber-frontend/app/photos/reveals/page.tsx` ✅

---

### 6. Merchant Promotions Discovery 🏪 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented (v0.3.28)

**What Exists:**
- Database: `merchant_profiles`, `promotions` tables
- Controllers: Full merchant and promotion management
- Features: Geolocated promotions, discount codes, payment tracking
- UI: Basic merchant dashboard, "Sponsored" styling in Local Pulse
- ✅ UI: `fwber-frontend/app/deals/page.tsx` - Consumer deals discovery

**Files:**
- `fwber-backend/app/Http/Controllers/MerchantController.php`
- `fwber-backend/database/migrations/2026_01_06_042019_create_merchant_profiles_table.php`
- `fwber-frontend/app/deals/page.tsx` ✅

---

### 7. Share-to-Unlock 🔗 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented (v0.3.28)

**What Exists:**
- Database: `share_unlocks` table
- Logic: Track shares, view counts, unlock thresholds
- Documented in `docs/features/VIRAL_GROWTH.md`
- ✅ UI: `fwber-frontend/app/share-unlock/page.tsx`

**Files:**
- `fwber-backend/database/migrations/2025_12_13_035232_create_share_unlocks_table.php`
- `docs/features/VIRAL_GROWTH.md`
- `fwber-frontend/app/share-unlock/page.tsx` ✅

---

### 8. Message Reactions 💬 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented

**What Exists:**
- Database: `chatroom_message_mentions` table
- ✅ UI: Reaction display and rendering in chatroom components
- Reaction picker and display on messages

**Files:**
- `fwber-frontend/app/chatrooms/[id]/page.tsx`
- `fwber-frontend/components/chatrooms/Chatroom.tsx`

---

### 9. Group-to-Group Matching 👥 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented (v0.3.28)

**What Exists:**
- Database: `group_matches` table
- Service: `GroupMatchingService` with matching logic
- API: Group match endpoints
- ✅ UI: `fwber-frontend/app/groups/matching/page.tsx`

**Files:**
- `fwber-backend/database/migrations/2025_12_31_182517_create_group_matches_table.php`
- `fwber-backend/app/Services/GroupMatchingService.php`
- `fwber-frontend/app/groups/matching/page.tsx` ✅

---

### 10. Matchmaker Bounties 💰 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented (v0.3.28)

**What Exists:**
- Database: `match_bounties`, `match_assists` tables
- Controller: `MatchBountyController`
- Logic: Token rewards for successful matches
- ✅ UI: `fwber-frontend/app/bounties/page.tsx`

**Files:**
- `fwber-backend/database/migrations/2025_12_29_000000_create_match_bounties_table.php`
- `fwber-frontend/app/bounties/page.tsx` ✅

---

### 11. Extended Viral Content 🤖 ✅ COMPLETE
**Backend Status:** Complete  
**Frontend Status:** ✅ UI Implemented (v0.3.28)

**What Exists:**
- Database: `viral_contents` table
- Types: roasts, fortunes, cosmic matches, nemesis finder
- AI generation logic for all types
- ✅ UI Pages:
  - `fwber-frontend/app/wingman/fortune/page.tsx` - Fortune predictions
  - `fwber-frontend/app/wingman/cosmic/page.tsx` - Zodiac compatibility
  - `fwber-frontend/app/wingman/vibe/page.tsx` - Vibe check (flags)
  - `fwber-frontend/app/wingman/roast/page.tsx` - Roast/Hype generator
  - `fwber-frontend/app/wingman/nemesis/page.tsx` - Nemesis finder

**Files:**
- `fwber-backend/database/migrations/2025_12_15_121931_create_viral_contents_table.php`
- `fwber-frontend/app/wingman/fortune/page.tsx` ✅
- `fwber-frontend/app/wingman/cosmic/page.tsx` ✅
- `fwber-frontend/app/wingman/vibe/page.tsx` ✅
- `fwber-frontend/app/wingman/roast/page.tsx` ✅
- `fwber-frontend/app/wingman/nemesis/page.tsx` ✅

---

## 🟡 Remaining Work (Not Yet Implemented)

### 12. Content Unlock System 🔓
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

### 13. E2E Encryption 🔐
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

### 17. Merchant Analytics Dashboard 📈
**Backend Status:** Partial  
**Frontend Status:** No UI

**What's Missing:**
- K-Factor (viral coefficient) tracking
- User retention metrics
- Engagement funnels
- Revenue analytics (token + fiat)

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

## Implementation Priority Matrix (Updated)

| Priority | Feature | Effort | Impact | Revenue | Status |
|----------|---------|--------|--------|---------|--------|
| ✅ Done | Achievements UI | Medium | High | Medium | COMPLETE |
| ✅ Done | Proximity Chatrooms | High | High | Low | COMPLETE |
| ✅ Done | Paid Photo Reveals | Low | Medium | High | COMPLETE |
| ✅ Done | Share-to-Unlock | Medium | High | Low | COMPLETE |
| ✅ Done | Merchant Discovery | Medium | Medium | High | COMPLETE |
| ✅ Done | Message Reactions | Low | Medium | Low | COMPLETE |
| ✅ Done | Bulletin Boards | Medium | Medium | Low | COMPLETE |
| ✅ Done | Profile Views | Low | Medium | Medium | COMPLETE |
| ✅ Done | Group Matching | Medium | Medium | Low | COMPLETE |
| ✅ Done | Extended Viral Content | Low | Medium | Low | COMPLETE |
| 🟡 Pending | Content Unlock System | Medium | Medium | High | Needs UI |
| 🟡 Pending | Merchant Analytics | High | Medium | High | Needs UI |
| 🔵 Low | E2E Encryption | High | Low | Low | Future |
| 🔵 Low | Group Events | Medium | Low | Low | Future |

---

## Next Steps (Phase 4C)

1. **Enable Feature Flags** - Start with `chatrooms`, `recommendations`, `ai_wingman`
2. **Enable Bulletin Board Routes** - Uncomment in `routes/api.php`
3. **Build Merchant Analytics Dashboard** - K-Factor tracking, retention metrics
4. **Replace Mock APIs** - Real content moderation (AWS Rekognition/Google Vision)
5. **Content Unlock System UI** - Generic premium content gates

---

*This document should be updated as features are implemented.*
*Last audit: January 10, 2026 - Phase 4B features verified as COMPLETE*
