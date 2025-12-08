'use client'

import ProtectedRoute from '@/components/ProtectedRoute'
import { useAuth } from '@/lib/auth-context'
import { useEffect, useState, useMemo } from 'react'
import { getNearbyUsers, getCurrentGeolocation, NearbyUser } from '@/lib/api/location'
import Link from 'next/link'
import { 
  PresenceIndicator, 
  ConnectionStatusBadge, 
  OnlineUsersList,
  ProximityPresenceView 
} from '@/components/realtime'
import { MapPin, Users, RefreshCw, Wifi } from 'lucide-react'

export default function NearbyPage() {
  const { token, logout } = useAuth()
  const [nearbyUsers, setNearbyUsers] = useState<NearbyUser[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [userLocation, setUserLocation] = useState<{ latitude: number; longitude: number } | null>(null)
  const [radius, setRadius] = useState(1000) // 1km default
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid')
  const [isRefreshing, setIsRefreshing] = useState(false)

  // Get user IDs for presence tracking
  const nearbyUserIds = useMemo(() => nearbyUsers.map(u => String(u.id)), [nearbyUsers])

  // Transform for ProximityPresenceView
  const proximityUsers = useMemo(() => nearbyUsers.map(u => ({
    id: String(u.id),
    displayName: u.display_name,
    distance: parseFloat(u.location.distance.replace(/[^\d.]/g, '')) / 1000, // Convert to km
  })), [nearbyUsers])

  useEffect(() => {
    const fetchNearbyUsers = async () => {
      if (!token) {
        // Let ProtectedRoute handle the redirect
        setLoading(false)
        return
      }

      try {
        setLoading(true)
        
        // Get user's current location
        const position = await getCurrentGeolocation()
        const location = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        }
        setUserLocation(location)

        // Fetch nearby users
        const response = await getNearbyUsers(token, {
          latitude: location.latitude,
          longitude: location.longitude,
          radius: radius,
          limit: 50,
        })
        
        setNearbyUsers(response.data)
        setError(null)
      } catch (err: any) {
        console.error('Failed to fetch nearby users:', err)
        setError(err.message || 'Failed to load nearby users.')
        if (err.message === 'Unauthenticated') {
          logout()
        }
      } finally {
        setLoading(false)
      }
    }

    fetchNearbyUsers()
  }, [token, logout, radius])

  const handleRadiusChange = (newRadius: number) => {
    setRadius(newRadius)
  }

  const handleRefresh = async () => {
    setIsRefreshing(true)
    // Trigger re-fetch
    if (token && userLocation) {
      try {
        const response = await getNearbyUsers(token, {
          latitude: userLocation.latitude,
          longitude: userLocation.longitude,
          radius: radius,
          limit: 50,
        })
        setNearbyUsers(response.data)
      } catch (err) {
        console.error('Failed to refresh:', err)
      }
    }
    setIsRefreshing(false)
  }

  const formatLastSeen = (lastUpdated: string) => {
    const date = new Date(lastUpdated)
    const now = new Date()
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
    
    if (diffInMinutes < 1) return 'Just now'
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`
    return `${Math.floor(diffInMinutes / 1440)}d ago`
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center text-red-500">
        <div className="text-center">
          <p className="text-xl mb-4">{error}</p>
          <button
            onClick={() => window.location.reload()}
            className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
          >
            Try Again
          </button>
        </div>
      </div>
    )
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-6xl mx-auto">
          <div className="bg-white rounded-lg shadow-md p-8 mb-6">
            {/* Header with Real-time Status */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-orange-100 rounded-lg">
                  <MapPin className="h-6 w-6 text-orange-600" />
                </div>
                <div>
                  <h1 className="text-3xl font-bold text-gray-900">People Nearby</h1>
                  <div className="flex items-center gap-2 mt-1">
                    <ConnectionStatusBadge />
                    <span className="text-sm text-gray-500">•</span>
                    <span className="text-sm text-gray-500">{nearbyUsers.length} found</span>
                  </div>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={handleRefresh}
                  disabled={isRefreshing}
                  className="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors disabled:opacity-50"
                >
                  <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                  Refresh
                </button>
                <div className="flex border border-gray-200 rounded-lg overflow-hidden">
                  <button
                    onClick={() => setViewMode('grid')}
                    className={`px-3 py-2 text-sm ${viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'}`}
                  >
                    Grid
                  </button>
                  <button
                    onClick={() => setViewMode('list')}
                    className={`px-3 py-2 text-sm ${viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'}`}
                  >
                    List
                  </button>
                </div>
              </div>
            </div>
            
            {userLocation && (
              <div className="mb-6 p-4 bg-blue-50 rounded-lg">
                <p className="text-sm text-blue-700">
                  <strong>Your Location:</strong> {userLocation.latitude.toFixed(6)}, {userLocation.longitude.toFixed(6)}
                </p>
              </div>
            )}

            <div className="mb-6">
              <label htmlFor="radius" className="block text-sm font-medium text-gray-700 mb-2">
                Search Radius: {radius}m
              </label>
              <input
                type="range"
                id="radius"
                min="100"
                max="10000"
                step="100"
                value={radius}
                onChange={(e) => handleRadiusChange(parseInt(e.target.value))}
                className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
              />
              <div className="flex justify-between text-xs text-gray-500 mt-1">
                <span>100m</span>
                <span>5km</span>
                <span>10km</span>
              </div>
            </div>

            {nearbyUsers.length === 0 ? (
              <div className="text-center py-12">
                <div className="text-gray-400 text-6xl mb-4">📍</div>
                <h3 className="text-xl font-semibold text-gray-600 mb-2">No one nearby</h3>
                <p className="text-gray-500">
                  Try increasing your search radius or check back later!
                </p>
              </div>
            ) : (
              <>
                {/* Online Users Summary */}
                <div className="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
                  <div className="flex items-center gap-2 mb-2">
                    <Users className="h-5 w-5 text-green-600" />
                    <span className="font-medium text-green-800">Online Now</span>
                  </div>
                  <OnlineUsersList userIds={nearbyUserIds} maxDisplay={6} showCount />
                </div>

                {/* User Grid/List */}
                <div className={viewMode === 'grid' 
                  ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" 
                  : "space-y-4"
                }>
                {nearbyUsers.map((user) => (
                  <div 
                    key={user.id} 
                    className={`border rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow ${
                      viewMode === 'list' ? 'flex items-center justify-between' : ''
                    }`}
                  >
                    <div className={viewMode === 'list' ? 'flex items-center gap-4' : ''}>
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                          <div className="relative">
                            <div className="w-12 h-12 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                              {user.display_name.charAt(0).toUpperCase()}
                            </div>
                            <div className="absolute -bottom-0.5 -right-0.5">
                              <PresenceIndicator userId={String(user.id)} size="sm" />
                            </div>
                          </div>
                          <div>
                            <h3 className="text-xl font-semibold text-gray-800">
                              {user.display_name}
                            </h3>
                            <PresenceIndicator userId={String(user.id)} showLabel size="sm" />
                          </div>
                        </div>
                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                          user.is_recent 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-yellow-100 text-yellow-800'
                        }`}>
                          {user.is_recent ? 'Active' : 'Inactive'}
                        </span>
                      </div>

                      <div className={`space-y-2 text-sm text-gray-600 ${viewMode === 'list' ? 'flex gap-4' : ''}`}>
                        {user.age && (
                          <p><strong>Age:</strong> {user.age}</p>
                        )}
                        {user.gender && (
                          <p><strong>Gender:</strong> {user.gender}</p>
                        )}
                        <p><strong>Distance:</strong> {user.location.distance}</p>
                        <p><strong>Last seen:</strong> {formatLastSeen(user.location.last_updated)}</p>
                      </div>
                    </div>

                    <div className={`mt-4 flex space-x-2 ${viewMode === 'list' ? 'mt-0' : ''}`}>
                      <Link
                        href={`/profile/${user.id}`}
                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded transition duration-200"
                      >
                        View Profile
                      </Link>
                      <button className="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded transition duration-200">
                        Message
                      </button>
                    </div>
                  </div>
                ))}
              </div>
              </>
            )}

            <div className="mt-8 text-center">
              <p className="text-sm text-gray-500">
                Found {nearbyUsers.length} people within {radius}m of your location
              </p>
            </div>
          </div>
        </div>
      </div>
    </ProtectedRoute>
  )
}
