/**
 * Tiered Relationship System
 * Progressive reveal of profile information based on relationship depth
 */

export enum RelationshipTier {
  DISCOVERY = 'discovery',           // Tier 1: Just browsing, AI photos only
  MATCHED = 'matched',               // Tier 2: Mutual match, limited real photos
  CONNECTED = 'connected',           // Tier 3: Active conversation, more photos
  ESTABLISHED = 'established',       // Tier 4: Deep connection, full access
  VERIFIED = 'verified'              // Tier 5: Met in person, complete trust
}

export interface TierRequirements {
  tier: RelationshipTier
  name: string
  description: string
  icon: string
  requirements: {
    description: string
    met: boolean
  }[]
  unlocks: string[]
  color: string
}

export interface TierProgress {
  currentTier: RelationshipTier
  nextTier: RelationshipTier | null
  messagesExchanged: number
  daysConnected: number
  hasMetInPerson: boolean
  photoUnlockCount: number
  realPhotosVisible: number
  aiPhotosVisible: number
}

/**
 * Calculate which tier a relationship is at based on interaction metrics
 */
export function calculateRelationshipTier(
  isMatched: boolean,
  messagesExchanged: number,
  daysConnected: number,
  hasMetInPerson: boolean,
  mutualConsent: boolean = true
): RelationshipTier {
  if (hasMetInPerson && mutualConsent) {
    return RelationshipTier.VERIFIED
  }

  if (messagesExchanged >= 50 && daysConnected >= 7) {
    return RelationshipTier.ESTABLISHED
  }

  if (messagesExchanged >= 10 && daysConnected >= 1) {
    return RelationshipTier.CONNECTED
  }

  if (isMatched) {
    return RelationshipTier.MATCHED
  }

  return RelationshipTier.DISCOVERY
}

/**
 * Get tier configuration and requirements
 */
export function getTierInfo(
  tier: RelationshipTier,
  messagesExchanged: number = 0,
  daysConnected: number = 0,
  hasMetInPerson: boolean = false
): TierRequirements {
  const tiers: Record<RelationshipTier, TierRequirements> = {
    [RelationshipTier.DISCOVERY]: {
      tier: RelationshipTier.DISCOVERY,
      name: 'Discovery',
      description: 'Browsing profiles and exploring matches',
      icon: '🔍',
      requirements: [
        {
          description: 'View profile',
          met: true
        }
      ],
      unlocks: [
        'AI-generated profile photos',
        'Basic profile information',
        'Interests and hobbies',
        'Bio (first 100 characters)'
      ],
      color: 'gray'
    },
    [RelationshipTier.MATCHED]: {
      tier: RelationshipTier.MATCHED,
      name: 'Matched',
      description: 'Mutual interest - conversation unlocked',
      icon: '💫',
      requirements: [
        {
          description: 'Both users liked each other',
          met: true
        }
      ],
      unlocks: [
        'Direct messaging',
        'Full bio access',
        '1-2 blurred real photos',
        'Last active status',
        'Response time preferences'
      ],
      color: 'blue'
    },
    [RelationshipTier.CONNECTED]: {
      tier: RelationshipTier.CONNECTED,
      name: 'Connected',
      description: 'Active conversation building trust',
      icon: '💬',
      requirements: [
        {
          description: 'Exchange at least 10 messages',
          met: messagesExchanged >= 10
        },
        {
          description: 'Stay connected for 1+ days',
          met: daysConnected >= 1
        }
      ],
      unlocks: [
        '3-5 real photos (unblurred)',
        'Video chat available',
        'Voice messages',
        'Share location (optional)',
        'Social media hints'
      ],
      color: 'purple'
    },
    [RelationshipTier.ESTABLISHED]: {
      tier: RelationshipTier.ESTABLISHED,
      name: 'Established',
      description: 'Deep connection and regular communication',
      icon: '❤️',
      requirements: [
        {
          description: 'Exchange at least 50 messages',
          met: messagesExchanged >= 50
        },
        {
          description: 'Stay connected for 7+ days',
          met: daysConnected >= 7
        }
      ],
      unlocks: [
        'Full photo gallery access',
        'Private albums (if shared)',
        'Contact information sharing',
        'Social media links',
        'Meeting scheduling',
        'Real-time location (if agreed)'
      ],
      color: 'pink'
    },
    [RelationshipTier.VERIFIED]: {
      tier: RelationshipTier.VERIFIED,
      name: 'Verified',
      description: 'Met in person - highest trust level',
      icon: '✅',
      requirements: [
        {
          description: 'Meet in person',
          met: hasMetInPerson
        },
        {
          description: 'Both confirm meeting',
          met: hasMetInPerson
        }
      ],
      unlocks: [
        'Complete profile access',
        'Private content sharing',
        'Emergency contact info',
        'Relationship status visibility',
        'Couple mode features'
      ],
      color: 'green'
    }
  }

  return tiers[tier]
}

/**
 * Determine how many real photos are visible at current tier
 */
export function getVisiblePhotoCount(
  tier: RelationshipTier,
  totalRealPhotos: number
): { real: number; ai: number; blurred: number } {
  switch (tier) {
    case RelationshipTier.DISCOVERY:
      return { real: 0, ai: 999, blurred: 0 } // Only AI photos

    case RelationshipTier.MATCHED:
      return { real: 0, ai: 999, blurred: Math.min(2, totalRealPhotos) } // 1-2 blurred real photos

    case RelationshipTier.CONNECTED:
      return { real: Math.min(5, totalRealPhotos), ai: 999, blurred: 0 } // 3-5 clear real photos

    case RelationshipTier.ESTABLISHED:
    case RelationshipTier.VERIFIED:
      return { real: totalRealPhotos, ai: 999, blurred: 0 } // All photos

    default:
      return { real: 0, ai: 999, blurred: 0 }
  }
}

/**
 * Get progress to next tier
 */
export function getTierProgress(
  currentTier: RelationshipTier,
  messagesExchanged: number,
  daysConnected: number,
  hasMetInPerson: boolean
): TierProgress {
  const tierOrder = [
    RelationshipTier.DISCOVERY,
    RelationshipTier.MATCHED,
    RelationshipTier.CONNECTED,
    RelationshipTier.ESTABLISHED,
    RelationshipTier.VERIFIED
  ]

  const currentIndex = tierOrder.indexOf(currentTier)
  const nextTier = currentIndex < tierOrder.length - 1 ? tierOrder[currentIndex + 1] : null

  const visiblePhotos = getVisiblePhotoCount(currentTier, 12) // Assume max 12 photos

  return {
    currentTier,
    nextTier,
    messagesExchanged,
    daysConnected,
    hasMetInPerson,
    photoUnlockCount: visiblePhotos.real,
    realPhotosVisible: visiblePhotos.real,
    aiPhotosVisible: visiblePhotos.ai
  }
}

/**
 * Check if user can unlock next tier
 */
export function canUnlockNextTier(
  currentTier: RelationshipTier,
  messagesExchanged: number,
  daysConnected: number,
  hasMetInPerson: boolean
): boolean {
  const calculatedTier = calculateRelationshipTier(
    currentTier !== RelationshipTier.DISCOVERY,
    messagesExchanged,
    daysConnected,
    hasMetInPerson
  )

  const tierOrder = [
    RelationshipTier.DISCOVERY,
    RelationshipTier.MATCHED,
    RelationshipTier.CONNECTED,
    RelationshipTier.ESTABLISHED,
    RelationshipTier.VERIFIED
  ]

  return tierOrder.indexOf(calculatedTier) > tierOrder.indexOf(currentTier)
}
