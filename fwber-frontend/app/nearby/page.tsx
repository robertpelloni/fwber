'use client'

import ProtectedRoute from '@/components/ProtectedRoute'
import { useAuth } from '@/lib/auth-context'
import { useEffect, useState } from 'react'
import { getNearbyUsers, getCurrentGeolocation, NearbyUser } from '@/lib/api/location'
import Link from 'next/link'

export default function NearbyPage() {
  const { token, logout } = useAuth()
  const [nearbyUsers, setNearbyUsers] = useState<NearbyUser[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [userLocation, setUserLocation] = useState<{ latitude: number; longitude: number } | null>(null)
  const [radius, setRadius] = useState(1000) // 1km default

  useEffect(() => {
    const fetchNearbyUsers = async () => {
      if (!token) {
        setError('Authentication token not found. Please log in.')
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
            <h1 className="text-3xl font-bold text-gray-900 mb-6">People Nearby</h1>
            
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
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {nearbyUsers.map((user) => (
                  <div key={user.id} className="border rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div className="flex items-center justify-between mb-4">
                      <h3 className="text-xl font-semibold text-gray-800">
                        {user.display_name}
                      </h3>
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        user.is_recent 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {user.is_recent ? 'Active' : 'Inactive'}
                      </span>
                    </div>

                    <div className="space-y-2 text-sm text-gray-600">
                      {user.age && (
                        <p><strong>Age:</strong> {user.age}</p>
                      )}
                      {user.gender && (
                        <p><strong>Gender:</strong> {user.gender}</p>
                      )}
                      <p><strong>Distance:</strong> {user.location.distance}</p>
                      <p><strong>Last seen:</strong> {formatLastSeen(user.location.last_updated)}</p>
                      <p><strong>Privacy:</strong> {user.privacy_level}</p>
                    </div>

                    <div className="mt-4 flex space-x-2">
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
