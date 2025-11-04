'use client'

import { useState, useEffect } from 'react'
import { RelationshipTier, calculateRelationshipTier } from '@/lib/relationshipTiers'

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

      // TODO: Replace with actual API call
      // const response = await fetch(`/api/matches/${matchId}/tier`)
      // const data = await response.json()

      // Simulated data for now
      const isMatched = true
      const messagesExchanged = Math.floor(Math.random() * 100)
      const daysConnected = Math.floor(Math.random() * 30)
      const hasMetInPerson = Math.random() > 0.8

      const tier = calculateRelationshipTier(
        isMatched,
        messagesExchanged,
        daysConnected,
        hasMetInPerson
      )

      setTierData({
        tier,
        isMatched,
        messagesExchanged,
        daysConnected,
        hasMetInPerson,
        lastTierUpgrade: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000),
        canUpgrade: tier < RelationshipTier.VERIFIED
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

  const incrementMessages = () => {
    if (!tierData) return
    
    const newMessagesCount = tierData.messagesExchanged + 1
    const newTier = calculateRelationshipTier(
      tierData.isMatched,
      newMessagesCount,
      tierData.daysConnected,
      tierData.hasMetInPerson
    )

    const hadUpgrade = newTier > tierData.tier

    setTierData({
      ...tierData,
      messagesExchanged: newMessagesCount,
      tier: newTier,
      lastTierUpgrade: hadUpgrade ? new Date() : tierData.lastTierUpgrade
    })

    return hadUpgrade
  }

  const markAsMetInPerson = async () => {
    if (!tierData || !matchId) return false

    try {
      // TODO: Replace with actual API call
      // await fetch(`/api/matches/${matchId}/met-in-person`, { method: 'POST' })

      const newTier = RelationshipTier.VERIFIED

      setTierData({
        ...tierData,
        hasMetInPerson: true,
        tier: newTier,
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
