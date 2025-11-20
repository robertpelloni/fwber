'use client';

import { useState, useEffect, useCallback } from 'react';
import Image from 'next/image';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute'
import { getMatches, performMatchAction, type Match, type MatchAction } from '@/lib/api/matches'
import { RelationshipTier } from '@/lib/relationshipTiers'
import RelationshipTierBadge from '@/components/RelationshipTierBadge'
import PhotoRevealGate from '@/components/PhotoRevealGate'
import MatchModal from '@/components/MatchModal'
import ReportModal from '@/components/ReportModal'
import { reportUser, blockUser } from '@/lib/api/safety'

export default function MatchesPage() {
  const { token, isAuthenticated, user } = useAuth()
  const [matches, setMatches] = useState<Match[]>([])
  const [currentMatchIndex, setCurrentMatchIndex] = useState(0)
  const [isLoading, setIsLoading] = useState(true)
  const [isPerformingAction, setIsPerformingAction] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [showMatchDetails, setShowMatchDetails] = useState(false)
  const [showTierInfo, setShowTierInfo] = useState(true)
  
  // Match Modal State
  const [isMatchModalOpen, setIsMatchModalOpen] = useState(false)
  const [matchedUserProfile, setMatchedUserProfile] = useState<{name: string, photoUrl: string} | null>(null)
  
  // Report Modal State
  const [isReportModalOpen, setIsReportModalOpen] = useState(false)

  // Simulated tier data - in real app, fetch from API
  const getCurrentTier = (match: Match): RelationshipTier => {
    // For discovery phase, all new matches start at DISCOVERY tier
    return RelationshipTier.DISCOVERY
  }

  const loadMatches = useCallback(async () => {
    if (!token) return

    try {
      setIsLoading(true)
      setError(null)
      const matchesData = await getMatches(token)
      setMatches(matchesData)
      setCurrentMatchIndex(0)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load matches')
    } finally {
      setIsLoading(false)
    }
  }, [token])

  useEffect(() => {
    if (isAuthenticated && token) {
      loadMatches()
    }
  }, [isAuthenticated, token, loadMatches])

  const advanceToNextMatch = useCallback(() => {
    if (currentMatchIndex < matches.length - 1) {
      setCurrentMatchIndex(prev => prev + 1)
    } else {
      // No more matches, reload
      loadMatches()
    }
  }, [currentMatchIndex, matches.length, loadMatches])

  const handleMatchAction = async (action: MatchAction) => {
    if (!token || currentMatchIndex >= matches.length) return

    const currentMatch = matches[currentMatchIndex]
    
    try {
      setIsPerformingAction(true)
      setError(null)

      const response = await performMatchAction(token, currentMatch.id, action)
      
      if (response.match_created) {
        setMatchedUserProfile({
          name: currentMatch.profile?.display_name || 'Match',
          photoUrl: currentMatch.profile?.photos?.[0]?.url || '/placeholder-user.jpg'
        })
        setIsMatchModalOpen(true)
      } else {
        advanceToNextMatch()
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to perform action')
    } finally {
      setIsPerformingAction(false)
    }
  }

  const handleCloseModal = () => {
    setIsMatchModalOpen(false)
    advanceToNextMatch()
  }

  const handleReport = async (reason: string, details: string) => {
    if (!token || !currentMatch) return
    
    // Use matched_user_id as the target
    const targetUserId = currentMatch.matched_user_id
    
    await reportUser(token, targetUserId, reason, details)
    
    if (confirm('Report submitted. Do you want to block this user as well?')) {
      try {
        await blockUser(token, targetUserId)
        // Move to next match
        advanceToNextMatch()
      } catch (err) {
        console.error('Failed to block after report', err)
      }
    }
  }

  const currentMatch = matches[currentMatchIndex]

  if (isLoading) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen flex items-center justify-center">
          <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
        </div>
      </ProtectedRoute>
    )
  }

  if (error) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="text-red-500 text-xl mb-4">Error: {error}</div>
            <button
              onClick={loadMatches}
              className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Try Again
            </button>
          </div>
        </div>
      </ProtectedRoute>
    )
  }

  if (matches.length === 0) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gray-50">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div className="text-center">
              <h1 className="text-3xl font-bold text-gray-900 mb-4">No Matches Found</h1>
              <p className="text-gray-600 mb-8">
                We couldn&apos;t find any potential matches for you right now. 
                Try updating your profile or preferences to find more people.
              </p>
              <div className="space-x-4">
                <a
                  href="/profile"
                  className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
                >
                  Update Profile
                </a>
                <button
                  onClick={loadMatches}
                  className="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700"
                >
                  Refresh Matches
                </button>
              </div>
            </div>
          </div>
        </div>
      </ProtectedRoute>
    )
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white shadow">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex justify-between items-center py-6">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">Discover Matches</h1>
                <p className="text-gray-600">
                  {currentMatchIndex + 1} of {matches.length} potential matches
                </p>
              </div>
              <div className="flex items-center space-x-4">
                <button
                  onClick={loadMatches}
                  className="bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-700"
                >
                  Refresh
                </button>
                <a
                  href="/dashboard"
                  className="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700"
                >
                  Dashboard
                </a>
              </div>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {currentMatch && (
            <div className="bg-white rounded-lg shadow-lg overflow-hidden">
              {/* Match Card */}
              <div className="relative">
                {/* Profile Photo with Tier-based Reveal */}
                <div className="aspect-w-3 aspect-h-4 bg-gray-200">
                  <div className="w-full h-96 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center relative">
                    {currentMatch.profile?.photos?.[0] ? (
                      <Image
                        src={currentMatch.profile.photos[0].url}
                        alt={currentMatch.profile.display_name || 'Profile'}
                        fill
                        sizes="(max-width: 768px) 100vw, 600px"
                        className="object-cover"
                        priority
                      />
                    ) : (
                      <div className="text-white text-6xl">
                        {currentMatch.profile?.display_name?.[0] || '?'}
                      </div>
                    )}
                    {/* Discovery tier overlay for AI photos */}
                    <div className="absolute top-4 left-4 bg-black/50 text-white text-xs px-3 py-1.5 rounded-full flex items-center gap-2">
                      <span>🤖</span>
                      <span>AI Generated</span>
                    </div>
                  </div>
                </div>

                {/* Match Info Overlay */}
                <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-6">
                  <div className="text-white">
                    <h2 className="text-2xl font-bold">
                      {currentMatch.profile?.display_name || 'Anonymous'}
                    </h2>
                    <p className="text-lg opacity-90">
                      {currentMatch.profile?.age && `${currentMatch.profile.age} years old`}
                    </p>
                    <p className="text-sm opacity-75">
                      {currentMatch.profile?.location?.city && currentMatch.profile?.location?.state
                        ? `${currentMatch.profile.location.city}, ${currentMatch.profile.location.state}`
                        : 'Location not specified'
                      }
                    </p>
                  </div>
                </div>

                {/* Compatibility Score & Tier Badge */}
                <div className="absolute top-4 right-4 flex flex-col gap-2 items-end">
                  <div className="bg-white bg-opacity-90 rounded-full px-3 py-1">
                    <span className="text-sm font-semibold text-gray-900">
                      {Math.round(currentMatch.compatibility_score * 100)}% Match
                    </span>
                  </div>
                  <RelationshipTierBadge
                    tier={getCurrentTier(currentMatch)}
                    compact={true}
                  />
                  <button
                    onClick={() => setIsReportModalOpen(true)}
                    className="bg-white bg-opacity-90 rounded-full p-2 text-gray-500 hover:text-red-600 transition-colors"
                    title="Report User"
                    aria-label="Report User"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                  </button>
                </div>
              </div>

              {/* Match Details */}
              <div className="p-6">
                {/* Tier Information Banner */}
                {showTierInfo && (
                  <div className="mb-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h4 className="font-semibold text-sm text-purple-900 dark:text-purple-100 mb-1">
                          🔍 Discovery Mode
                        </h4>
                        <p className="text-xs text-purple-700 dark:text-purple-300">
                          You&apos;re viewing AI-generated photos. Match to unlock real photos and start chatting!
                        </p>
                      </div>
                      <button
                        onClick={() => setShowTierInfo(false)}
                        className="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 text-xs ml-2"
                      >
                        ✕
                      </button>
                    </div>
                  </div>
                )}

                {/* Bio */}
                {currentMatch.profile?.bio && (
                  <div className="mb-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">About</h3>
                    <p className="text-gray-700">
                      {/* Show preview in discovery mode */}
                      {currentMatch.profile.bio.length > 150 
                        ? `${currentMatch.profile.bio.slice(0, 150)}... (Match to read more)`
                        : currentMatch.profile.bio
                      }
                    </p>
                  </div>
                )}

                {/* Looking For */}
                {currentMatch.profile?.looking_for && currentMatch.profile.looking_for.length > 0 && (
                  <div className="mb-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">Looking For</h3>
                    <div className="flex flex-wrap gap-2">
                      {currentMatch.profile.looking_for.map((item, index) => (
                        <span
                          key={index}
                          className="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full"
                        >
                          {item}
                        </span>
                      ))}
                    </div>
                  </div>
                )}

                {/* Match Actions */}
                <div className="flex justify-center space-x-4 pt-6 border-t">
                  <button
                    onClick={() => handleMatchAction('pass')}
                    disabled={isPerformingAction}
                    className="bg-gray-500 hover:bg-gray-600 text-white px-8 py-3 rounded-full text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Pass
                  </button>
                  
                  <button
                    onClick={() => setShowMatchDetails(!showMatchDetails)}
                    className="bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded-full text-lg font-semibold"
                  >
                    {showMatchDetails ? 'Hide Details' : 'View Details'}
                  </button>
                  
                  <button
                    onClick={() => handleMatchAction('like')}
                    disabled={isPerformingAction}
                    className="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-full text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Like
                  </button>
                  
                  <button
                    onClick={() => handleMatchAction('super_like')}
                    disabled={isPerformingAction}
                    className="bg-purple-500 hover:bg-purple-600 text-white px-8 py-3 rounded-full text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Super Like
                  </button>
                </div>

                {/* Detailed Match Information */}
                {showMatchDetails && (
                  <div className="mt-6 pt-6 border-t">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Detailed Information</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <span className="font-medium text-gray-700">Gender:</span>
                        <span className="ml-2 text-gray-600 capitalize">
                          {currentMatch.profile?.gender || 'Not specified'}
                        </span>
                      </div>
                      <div>
                        <span className="font-medium text-gray-700">Max Distance:</span>
                        <span className="ml-2 text-gray-600">
                          {currentMatch.profile?.location?.max_distance || 'Not specified'} miles
                        </span>
                      </div>
                      <div>
                        <span className="font-medium text-gray-700">Compatibility Score:</span>
                        <span className="ml-2 text-gray-600">
                          {Math.round(currentMatch.compatibility_score * 100)}%
                        </span>
                      </div>
                      <div>
                        <span className="font-medium text-gray-700">Profile Complete:</span>
                        <span className="ml-2 text-gray-600">
                          {currentMatch.profile?.profile_complete ? 'Yes' : 'No'}
                        </span>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </main>

        {/* Match Modal */}
        {matchedUserProfile && (
          <MatchModal
            isOpen={isMatchModalOpen}
            onClose={handleCloseModal}
            matchedUser={matchedUserProfile}
            currentUser={{
              photoUrl: user?.profile?.avatarUrl || '/placeholder-user.jpg'
            }}
          />
        )}

        {currentMatch && (
          <ReportModal
            isOpen={isReportModalOpen}
            onClose={() => setIsReportModalOpen(false)}
            onSubmit={handleReport}
            userName={currentMatch.profile?.display_name || 'User'}
          />
        )}
      </div>
    </ProtectedRoute>
  )
}
