'use client'

import { useState, useEffect } from 'react'
import { RelationshipTier } from '@/lib/relationshipTiers'
import { getMatchTier, incrementMatchMessages, markMatchMetInPerson } from '@/lib/api/tierApi'

export interface UserTierData {
  tier: RelationshipTier
  isMatched: boolean
  messagesExchanged: number
  daysConnected: number
  hasMetInPerson: boolean
  lastTierUpgrade: Date | null
  canUpgrade: boolean
}

/**
 * Hook to fetch and manage relationship tier data for a matched user
 * 
 * @param userId - The ID of the matched user
 * @param matchId - The ID of the match relationship
 * @returns Tier data and loading state
 */
export function useRelationshipTier(userId: number | null, matchId: number | null) {
  const [tierData, setTierData] = useState<UserTierData | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const fetchTierData = async () => {
    if (!userId || !matchId) return

    try {
      setIsLoading(true)
      setError(null)

      const response = await getMatchTier(matchId)

      setTierData({
        tier: response.current_tier as RelationshipTier,
        isMatched: true,
        messagesExchanged: response.messages_exchanged,
        daysConnected: response.days_connected,
        hasMetInPerson: response.has_met_in_person,
        lastTierUpgrade: new Date(response.updated_at),
        canUpgrade: response.current_tier !== 'verified'
      })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load tier data')
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    if (!userId || !matchId) {
      setIsLoading(false)
      return
    }

    fetchTierData()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [userId, matchId])

  const incrementMessages = async () => {
    if (!tierData || !matchId) return false
    
    try {
      const response = await incrementMatchMessages(matchId)
      
      const hadUpgrade = response.tier_upgraded

      setTierData({
        ...tierData,
        messagesExchanged: response.messages_exchanged,
        tier: response.current_tier as RelationshipTier,
        lastTierUpgrade: hadUpgrade ? new Date() : tierData.lastTierUpgrade
      })

      return hadUpgrade
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update message count')
      return false
    }
  }

  const markAsMetInPerson = async () => {
    if (!tierData || !matchId) return false

    try {
      const response = await markMatchMetInPerson(matchId)

      setTierData({
        ...tierData,
        hasMetInPerson: response.has_met_in_person,
        tier: response.current_tier as RelationshipTier,
        lastTierUpgrade: new Date()
      })

      return true
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update meeting status')
      return false
    }
  }

  return {
    tierData,
    isLoading,
    error,
    incrementMessages,
    markAsMetInPerson,
    refresh: fetchTierData
  }
}

/**
 * Hook to get tier data for discovery mode (non-matched users)
 * Always returns DISCOVERY tier with default values
 */
export function useDiscoveryTier() {
  return {
    tierData: {
      tier: RelationshipTier.DISCOVERY,
      isMatched: false,
      messagesExchanged: 0,
      daysConnected: 0,
      hasMetInPerson: false,
      lastTierUpgrade: null,
      canUpgrade: true
    },
    isLoading: false,
    error: null
  }
}
