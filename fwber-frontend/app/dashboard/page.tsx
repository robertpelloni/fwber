'use client'

import { useAuth } from '@/lib/auth-context'
import ProtectedRoute from '@/components/ProtectedRoute'
import Link from 'next/link'

export default function DashboardPage() {
  const { user, logout } = useAuth()

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white shadow">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex justify-between items-center py-6">
              <div className="flex items-center">
                <h1 className="text-3xl font-bold text-gray-900">FWBer</h1>
              </div>
              <div className="flex items-center space-x-4">
                <span className="text-sm text-gray-700">
                  Welcome, {user?.name || user?.email}!
                </span>
                <button
                  onClick={logout}
                  className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                  Logout
                </button>
              </div>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
          <div className="px-4 py-6 sm:px-0">
            <div className="border-4 border-dashed border-gray-200 rounded-lg p-8">
              <div className="text-center">
                <h2 className="text-2xl font-bold text-gray-900 mb-4">
                  Welcome to your FWBer Dashboard!
                </h2>
                
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                  {/* Profile Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Profile</h3>
                    <p className="text-gray-600 mb-4">
                      {user?.profile?.bio 
                        ? 'Profile set up'
                        : 'Complete your profile to get started'
                      }
                    </p>
                    <Link
                      href="/profile"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200"
                    >
                      Manage Profile
                    </Link>
                  </div>

                  {/* Matches Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Matches</h3>
                    <p className="text-gray-600 mb-4">
                      Discover and connect with compatible people
                    </p>
                    <Link
                      href="/matches"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-600 bg-green-100 hover:bg-green-200"
                    >
                      View Matches
                    </Link>
                  </div>

                  {/* Messages Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Messages</h3>
                    <p className="text-gray-600 mb-4">
                      Chat with your matches
                    </p>
                    <Link
                      href="/messages"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-purple-600 bg-purple-100 hover:bg-purple-200"
                    >
                      View Messages
                    </Link>
                  </div>

                  {/* Bulletin Boards Card */}
                  <div className="bg-white p-6 rounded-lg shadow">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">📍 Bulletin Boards</h3>
                    <p className="text-gray-600 mb-4">
                      Connect with your local community
                    </p>
                    <Link
                      href="/bulletin-boards"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-orange-600 bg-orange-100 hover:bg-orange-200"
                    >
                      Local Boards
                    </Link>
                  </div>

                  {/* AI Recommendations Card */}
                  <div className="bg-gradient-to-br from-purple-50 to-blue-50 p-6 rounded-lg shadow border border-purple-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">🤖 AI Recommendations</h3>
                    <p className="text-gray-600 mb-4">
                      Get personalized recommendations powered by AI
                    </p>
                    <Link
                      href="/recommendations"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700"
                    >
                      View Recommendations
                    </Link>
                  </div>

                  {/* WebSocket Real-time Card */}
                  <div className="bg-gradient-to-br from-green-50 to-teal-50 p-6 rounded-lg shadow border border-green-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">⚡ Real-time Communication</h3>
                    <p className="text-gray-600 mb-4">
                      WebSocket-powered real-time chat and notifications
                    </p>
                    <Link
                      href="/websocket"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700"
                    >
                      Test WebSocket
                    </Link>
                  </div>

                  {/* AI Content Generation Card */}
                  <div className="bg-gradient-to-br from-indigo-50 to-purple-50 p-6 rounded-lg shadow border border-indigo-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">🎨 AI Content Generation</h3>
                    <p className="text-gray-600 mb-4">
                      Create engaging content with AI-powered assistance
                    </p>
                    <Link
                      href="/content-generation"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700"
                    >
                      Generate Content
                    </Link>
                  </div>

                  {/* Chatrooms Card */}
                  <div className="bg-gradient-to-br from-pink-50 to-rose-50 p-6 rounded-lg shadow border border-pink-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">💬 Real-time Chatrooms</h3>
                    <p className="text-gray-600 mb-4">
                      Join location-based chatrooms and connect with people in your area
                    </p>
                    <Link
                      href="/chatrooms"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700"
                    >
                      Join Chatrooms
                    </Link>
                  </div>

                  {/* Proximity Chatrooms Card */}
                  <div className="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-lg shadow border border-blue-200">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">🌐 Proximity Chatrooms</h3>
                    <p className="text-gray-600 mb-4">
                      Connect with people nearby for networking, social interaction, and professional opportunities
                    </p>
                    <Link
                      href="/proximity-chatrooms"
                      className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                    >
                      Find Nearby Chatrooms
                    </Link>
                  </div>
                </div>

                {/* User Info */}
                <div className="mt-8 bg-white p-6 rounded-lg shadow">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="font-medium text-gray-700">Email:</span>
                      <span className="ml-2 text-gray-600">{user?.email}</span>
                    </div>
                    <div>
                      <span className="font-medium text-gray-700">Member since:</span>
                      <span className="ml-2 text-gray-600">
                        {user?.createdAt ? new Date(user.createdAt).toLocaleDateString() : 'N/A'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium text-gray-700">Email verified:</span>
                      <span className="ml-2 text-gray-600">
                        {user?.emailVerifiedAt ? 'Yes' : 'No'}
                      </span>
                    </div>
                    <div>
                      <span className="font-medium text-gray-700">Last updated:</span>
                      <span className="ml-2 text-gray-600">
                        {user?.updatedAt ? new Date(user.updatedAt).toLocaleString() : 'N/A'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Quick Actions */}
                <div className="mt-8">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                  <div className="flex flex-wrap justify-center gap-4">
                    <Link
                      href="/test-auth"
                      className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      API Test Page
                    </Link>
                    <Link
                      href="/profile/edit"
                      className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Edit Profile
                    </Link>
                    <Link
                      href="/settings"
                      className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Settings
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}