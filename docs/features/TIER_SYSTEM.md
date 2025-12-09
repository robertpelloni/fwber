# Tiered Relationship System

## Overview

The Tiered Relationship System is a privacy-first progressive reveal mechanism that controls what information users can see about each other based on the depth of their connection. This creates a safe, trust-building experience where users unlock more access as their relationship develops.

## The Five Tiers

### 🔍 Tier 1: Discovery
**Requirements:** None (default state)

**What's Visible:**
- AI-generated photos only (no real photos)
- Basic profile info (name, age, location)
- Bio preview (first 150 characters)
- Compatibility score

**Actions Available:**
- Like/Pass/Super Like
- View limited profile

**Purpose:** Safe browsing without exposing real identity. Users can explore potential matches without privacy concerns.

---

### 💫 Tier 2: Matched
**Requirements:** Mutual like (both users liked each other)

**What's Visible:**
- 1-2 blurred real photos
- Full bio
- Complete profile fields
- Chat access unlocked

**Actions Available:**
- Send/receive messages
- View full profile
- Share interests

**Purpose:** Initial connection established. Users can start chatting while maintaining some privacy until trust builds.

---

### 💬 Tier 3: Connected
**Requirements:**
- 10+ messages exchanged
- 1+ days since matching

**What's Visible:**
- 3-5 clear real photos
- Voice/video call access
- Activity status
- Last seen info

**Actions Available:**
- Voice calling
- Video chat requests
- Photo sharing in chat
- Extended profile access

**Purpose:** Active conversation proves genuine interest. Users unlock clearer photos as they engage meaningfully.

---

### ❤️ Tier 4: Established
**Requirements:**
- 50+ messages exchanged
- 7+ days connected
- Consistent engagement

**What's Visible:**
- Full photo gallery (all real photos)
- Contact information (if shared)
- Social media links
- Complete profile access

**Actions Available:**
- Full messaging features
- Photo/video sharing
- Location sharing (opt-in)
- Contact exchange

**Purpose:** Deep connection proven through sustained interaction. Users feel comfortable sharing complete information.

---

### ✅ Tier 5: Verified
**Requirements:**
- Met in person (both users confirm)
- All Tier 4 requirements met

**What's Visible:**
- Complete unrestricted access
- Private content (if any)
- All features unlocked
- Verified badge on profile

**Actions Available:**
- Everything
- Verified status shown to others
- Enhanced match priority

**Purpose:** Real-world meeting confirms authenticity. Maximum trust and access granted.

## Implementation

### Frontend Components

#### `RelationshipTierBadge`
Displays current tier status with icon, color, and progress.

```tsx
<RelationshipTierBadge
  tier={RelationshipTier.CONNECTED}
  messagesExchanged={15}
  daysConnected={3}
  showProgress={true}
  compact={false}
/>
```

#### `PhotoRevealGate`
Controls photo visibility based on tier.

```tsx
<PhotoRevealGate
  photos={userPhotos}
  currentTier={tier}
  messagesExchanged={messages}
  daysConnected={days}
  onUnlockClick={() => navigateToChat()}
/>
```

#### `TierUpgradeNotification`
Celebrates tier progression with modal.

```tsx
{showUpgrade && (
  <TierUpgradeNotification
    previousTier={oldTier}
    newTier={newTier}
    onClose={() => setShowUpgrade(false)}
  />
)}
```

### Hooks

#### `useRelationshipTier`
Manages tier data for matched users.

```tsx
const { tierData, incrementMessages, markAsMetInPerson } = useRelationshipTier(userId, matchId)
```

#### `useDiscoveryTier`
Returns discovery tier for non-matched users.

```tsx
const { tierData } = useDiscoveryTier()
```

### Core Logic

#### Tier Calculation
```typescript
import { calculateRelationshipTier, RelationshipTier } from '@/lib/relationshipTiers'

const tier = calculateRelationshipTier(
  isMatched,        // true if mutual match
  messagesExchanged, // total message count
  daysConnected,    // days since match
  hasMetInPerson    // both confirmed meeting
)
```

#### Photo Visibility
```typescript
import { getVisiblePhotoCount } from '@/lib/relationshipTiers'

const visibility = getVisiblePhotoCount(tier, totalRealPhotos)
// Returns: { real: number, ai: 999, blurred: number }
```

#### Progress Tracking
```typescript
import { getTierProgress } from '@/lib/relationshipTiers'

const progress = getTierProgress(tier, messages, days, metInPerson)
// Shows progress to next tier
```

## Backend Integration

### Database Schema

```sql
-- Relationship tier tracking
CREATE TABLE relationship_tiers (
  id SERIAL PRIMARY KEY,
  match_id INTEGER REFERENCES matches(id),
  current_tier INTEGER NOT NULL DEFAULT 0,
  messages_exchanged INTEGER NOT NULL DEFAULT 0,
  days_connected INTEGER NOT NULL DEFAULT 0,
  has_met_in_person BOOLEAN NOT NULL DEFAULT FALSE,
  last_tier_upgrade TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Photo type tracking
ALTER TABLE photos ADD COLUMN photo_type VARCHAR(10) DEFAULT 'real';
-- Values: 'ai' | 'real'
```

### API Endpoints

#### Get Tier Data
```
GET /api/matches/{matchId}/tier
Response: {
  tier: 2,
  isMatched: true,
  messagesExchanged: 15,
  daysConnected: 3,
  hasMetInPerson: false,
  canUpgrade: true
}
```

#### Confirm In-Person Meeting
```
POST /api/matches/{matchId}/met-in-person
Response: {
  success: true,
  newTier: 5,
  upgradedAt: "2025-11-04T12:00:00Z"
}
```

#### Increment Message Count
```
POST /api/matches/{matchId}/messages/increment
Response: {
  messagesExchanged: 16,
  tierUpgraded: false,
  currentTier: 2
}
```

## Privacy & Safety Benefits

### For Users
- **Gradual Trust Building:** No pressure to reveal everything immediately
- **Catfish Prevention:** Real photos only after demonstrated engagement
- **Safety First:** AI photos protect identity during discovery
- **Scammer Deterrent:** Bots/scammers unlikely to sustain 50+ message conversation
- **Clear Progression:** Users know exactly what unlocks when

### For Women & Vulnerable Users
- **Reduced Harassment:** Fake photos until trust established
- **Safe Exploration:** Browse without privacy concerns
- **Engagement Proof:** Only serious matches reach higher tiers
- **Control:** Progressive reveals give time to assess safety

### For Platform
- **Increased Engagement:** Users motivated to chat to unlock photos
- **Quality Over Quantity:** Encourages meaningful conversations
- **Reduced Fake Profiles:** Multi-tier verification process
- **User Retention:** Progression system creates investment
- **Safety Reputation:** Privacy-first approach attracts users

## Testing

### Demo Page
Visit `/tier-demo` to interactively test the tier system:
- Toggle between all 5 tiers
- Adjust message count and days
- See photo reveals in real-time
- Test tier upgrade notifications

### Backend Integration
Backend integration is complete. The `RelationshipTierController` provides full API support for tier management, including:
- `GET /api/matches/{matchId}/tier` - Get tier progress
- `PUT /api/matches/{matchId}/tier` - Update tier metrics (increment messages, mark met in person)
- `GET /api/matches/{matchId}/tier/photos` - Get tier-based photos with progressive unlock

## Future Enhancements

### AI Photo Generation
- Integration with Stable Diffusion/DALL-E
- Style-consistent photo sets
- User-guided generation prompts
- Automatic privacy watermarking

### Enhanced Verification
- Video verification for Tier 5
- GPS check-in for in-person meetings
- Photo verification (face match)
- Social media cross-verification

### Gamification
- Tier achievement badges
- Unlock celebrations
- Progress notifications
- Tier leaderboards (optional)

### Advanced Features
- Custom tier timelines per relationship
- Mutual tier unlock agreements
- Temporary tier boosts (premium feature)
- Tier-based matching priority

## Migration Path

### Phase 1: Frontend Only ✅
- Core tier logic implemented
- UI components created
- Demo page functional
- Simulated data working

### Phase 2: Backend Integration ✅
- Database schema creation (relationship_tiers table)
- API endpoint development (RelationshipTierController)
- WebSocket tier updates
- Real-time notifications

### Phase 3: AI Photos 📋
- AI generation pipeline
- Style consistency system
- User prompt interface
- Photo type separation

### Phase 4: Verification 📋
- In-person meeting confirmation
- Video verification system
- Enhanced safety checks
- Verified badge rollout

## Configuration

All tier thresholds are configurable in `lib/relationshipTiers.ts`:

```typescript
// Message requirements
CONNECTED_MESSAGES = 10
ESTABLISHED_MESSAGES = 50

// Day requirements
CONNECTED_DAYS = 1
ESTABLISHED_DAYS = 7

// Photo reveal counts
MATCHED_REAL_PHOTOS = 2      // blurred
CONNECTED_REAL_PHOTOS = 5    // clear
ESTABLISHED_REAL_PHOTOS = 999 // all
```

Adjust these values to tune the progression speed based on user feedback and platform goals.

---

**Status:** Frontend and backend implementation complete
**Version:** 1.1.0
**Last Updated:** December 7, 2025
