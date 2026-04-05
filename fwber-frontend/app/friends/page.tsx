'use client'

import { useCallback, useEffect, useMemo, useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { useAuth } from '@/lib/auth-context'
import {
  getFriends,
  getFriendRequests,
  searchUsers,
  sendFriendRequest,
  respondToFriendRequest,
  removeFriend,
  type FriendUser,
  type FriendRequest,
} from '@/lib/api/friends'
import { Search, UserPlus, Users, Mail, Check, X, Trash2 } from 'lucide-react'

export default function FriendsPage() {
  const { token } = useAuth()
  const [friends, setFriends] = useState<FriendUser[]>([])
  const [requests, setRequests] = useState<FriendRequest[]>([])
  const [results, setResults] = useState<FriendUser[]>([])
  const [query, setQuery] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isSearching, setIsSearching] = useState(false)

  const loadData = useCallback(async () => {
    if (!token) return

    setIsLoading(true)
    try {
      const [friendsData, requestsData] = await Promise.all([
        getFriends(token),
        getFriendRequests(token),
      ])

      setFriends(Array.isArray(friendsData) ? friendsData : [])
      setRequests(Array.isArray(requestsData) ? requestsData : [])
    } finally {
      setIsLoading(false)
    }
  }, [token])

  useEffect(() => {
    void loadData()
  }, [loadData])

  const handleSearch = async () => {
    if (!token || !query.trim()) {
      setResults([])
      return
    }

    setIsSearching(true)
    try {
      const data = await searchUsers(token, query.trim())
      setResults(Array.isArray(data) ? data : [])
    } finally {
      setIsSearching(false)
    }
  }

  const handleSendRequest = async (friendId: number) => {
    if (!token) return
    await sendFriendRequest(token, friendId)
    alert('Friend request sent!')
  }

  const handleRespond = async (userId: number, status: 'accepted' | 'declined') => {
    if (!token) return

    await respondToFriendRequest(token, userId, status)
    await loadData()
  }

  const handleRemoveFriend = async (friendId: number) => {
    if (!token) return
    await removeFriend(token, friendId)
    await loadData()
  }

  const existingFriendIds = useMemo(() => new Set(friends.map((friend) => friend.id)), [friends])
  const existingRequestIds = useMemo(() => new Set(requests.map((request) => request.user_id)), [requests])

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Friends" />
        <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Friends</h1>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
              Restore the lightweight social graph around trusted connections without dragging the app back into bloated feed mechanics.
            </p>
          </div>

          <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section className="space-y-6">
              <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div className="mb-4 flex items-center gap-2">
                  <Search className="h-5 w-5 text-blue-500" />
                  <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Find people</h2>
                </div>
                <div className="flex flex-col gap-3 sm:flex-row">
                  <input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search by name or email"
                    className="flex-1 rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                  />
                  <button
                    onClick={handleSearch}
                    disabled={isSearching}
                    className="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                  >
                    {isSearching ? 'Searching…' : 'Search'}
                  </button>
                </div>

                <div className="mt-4 grid gap-3">
                  {results.map((user) => {
                    const isFriend = existingFriendIds.has(user.id)
                    const hasPendingRequest = existingRequestIds.has(user.id)

                    return (
                      <div key={user.id} className="shadow rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex items-start justify-between gap-4">
                          <div>
                            <h3 className="font-semibold text-gray-900 dark:text-white">{user.name}</h3>
                            <p className="mt-1 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                              <Mail className="h-4 w-4" />
                              {user.email}
                            </p>
                          </div>
                          <div>
                            {isFriend ? (
                              <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                Already friends
                              </span>
                            ) : hasPendingRequest ? (
                              <span className="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Request pending
                              </span>
                            ) : (
                              <button
                                onClick={() => handleSendRequest(user.id)}
                                className="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700"
                              >
                                <UserPlus className="h-4 w-4" />
                                Send Request
                              </button>
                            )}
                          </div>
                        </div>
                      </div>
                    )
                  })}
                </div>
              </div>
            </section>

            <section className="space-y-6">
              <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div className="mb-4 flex items-center gap-2">
                  <Users className="h-5 w-5 text-pink-500" />
                  <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Your Friends</h2>
                </div>

                {isLoading ? (
                  <p className="text-sm text-gray-500">Loading friends…</p>
                ) : friends.length === 0 ? (
                  <p className="text-sm text-gray-500">No accepted friends yet.</p>
                ) : (
                  <div className="space-y-3">
                    {friends.map((friend) => (
                      <div key={friend.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex items-center justify-between gap-4">
                          <div>
                            <h3 className="font-semibold text-gray-900 dark:text-white">{friend.name}</h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400">{friend.email}</p>
                          </div>
                          <button
                            onClick={() => handleRemoveFriend(friend.id)}
                            className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-900/40 dark:hover:bg-red-950/20"
                          >
                            <Trash2 className="h-4 w-4" />
                            Remove
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div className="mb-4 flex items-center gap-2">
                  <UserPlus className="h-5 w-5 text-orange-500" />
                  <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Friend Requests</h2>
                </div>

                {isLoading ? (
                  <p className="text-sm text-gray-500">Loading requests…</p>
                ) : requests.length === 0 ? (
                  <p className="text-sm text-gray-500">No pending requests right now.</p>
                ) : (
                  <div className="space-y-3">
                    {requests.map((request) => (
                      <div key={request.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex items-start justify-between gap-4">
                          <div>
                            <h3 className="font-semibold text-gray-900 dark:text-white">{request.user?.name || 'Unknown User'}</h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400">{request.user?.email}</p>
                          </div>
                          <div className="flex gap-2">
                            <button
                              onClick={() => handleRespond(request.user_id, 'accepted')}
                              className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700"
                            >
                              <Check className="h-4 w-4" />
                              Accept
                            </button>
                            <button
                              onClick={() => handleRespond(request.user_id, 'declined')}
                              className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                              <X className="h-4 w-4" />
                              Decline
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </section>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
