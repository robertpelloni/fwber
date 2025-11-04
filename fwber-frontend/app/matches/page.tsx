'use client'

import { useState, useEffect } from 'react'
import { useAuth } from '@/lib/auth-context'
import ProtectedRoute from '@/components/ProtectedRoute'
import { getMatches, performMatchAction, type Match, type MatchAction } from '@/lib/api/matches'

export default function MatchesPage() {
  const { token, isAuthenticated } = useAuth()
  const [matches, setMatches] = useState<Match[]>([])
  const [currentMatchIndex, setCurrentMatchIndex] = useState(0)
  const [isLoading, setIsLoading] = useState(true)
  const [isPerformingAction, setIsPerformingAction] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [showMatchDetails, setShowMatchDetails] = useState(false)

  useEffect(() => {
    if (isAuthenticated && token) {
      loadMatches()
    }
  }, [isAuthenticated, token])

  const loadMatches = async () => {
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
  }

  const handleMatchAction = async (action: MatchAction) => {
    if (!token || currentMatchIndex >= matches.length) return

    const currentMatch = matches[currentMatchIndex]
    
    try {
      setIsPerformingAction(true)
      setError(null)

      await performMatchAction(token, currentMatch.id, action)
      
      // Move to next match
      if (currentMatchIndex < matches.length - 1) {
        setCurrentMatchIndex(prev => prev + 1)
      } else {
        // No more matches, reload
        await loadMatches()
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to perform action')
    } finally {
      setIsPerformingAction(false)
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
                {/* Profile Photo */}
                <div className="aspect-w-3 aspect-h-4 bg-gray-200">
                  <div className="w-full h-96 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                    {currentMatch.profile?.photos?.[0] ? (
                      <img
                        src={currentMatch.profile.photos[0].url}
                        alt={currentMatch.profile.display_name || 'Profile'}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="text-white text-6xl">
                        {currentMatch.profile?.display_name?.[0] || '?'}
                      </div>
                    )}
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

                {/* Compatibility Score */}
                <div className="absolute top-4 right-4 bg-white bg-opacity-90 rounded-full px-3 py-1">
                  <span className="text-sm font-semibold text-gray-900">
                    {Math.round(currentMatch.compatibility_score * 100)}% Match
                  </span>
                </div>
              </div>

              {/* Match Details */}
              <div className="p-6">
                {/* Bio */}
                {currentMatch.profile?.bio && (
                  <div className="mb-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">About</h3>
                    <p className="text-gray-700">{currentMatch.profile.bio}</p>
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
      </div>
    </ProtectedRoute>
  )
}
