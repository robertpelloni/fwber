'use client'

import { useState, useEffect } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { 
  Coins, Users, ArrowLeft, Clock, 
  Filter, ChevronDown, Target, Heart,
  RefreshCw, Crown
} from 'lucide-react'
import Link from 'next/link'
import Image from 'next/image'

interface BountyUser {
  id: number
  name: string
  avatar_url: string | null
  profile: {
    display_name: string
    age: number
    gender: string
  } | null
  photos: {
    id: number
    url: string
    is_primary: boolean
  }[]
}

interface Bounty {
  id: number
  slug: string
  token_reward: number
  status: string
  description: string | null
  expires_at: string | null
  created_at: string
  user: BountyUser
}

interface BountiesResponse {
  data: Bounty[]
  current_page: number
  last_page: number
  total: number
}

const sortOptions = [
  { value: 'reward', label: 'Highest Reward' },
  { value: 'newest', label: 'Newest First' },
  { value: 'expiring', label: 'Expiring Soon' },
]

function getTimeRemaining(expiresAt: string | null): string {
  if (!expiresAt) return 'No expiry'
  
  const now = new Date()
  const expires = new Date(expiresAt)
  const diff = expires.getTime() - now.getTime()
  
  if (diff <= 0) return 'Expired'
  
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(hours / 24)
  
  if (days > 7) return `${Math.floor(days / 7)}w left`
  if (days > 0) return `${days}d left`
  if (hours > 0) return `${hours}h left`
  return 'Expiring soon!'
}

function formatTokenReward(tokens: number): string {
  if (tokens >= 1000) {
    return `${(tokens / 1000).toFixed(1)}k`
  }
  return tokens.toString()
}

export default function BountiesPage() {
  const [bounties, setBounties] = useState<Bounty[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [sortBy, setSortBy] = useState('reward')
  const [minReward, setMinReward] = useState<number | null>(null)
  const [showFilters, setShowFilters] = useState(false)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)

  useEffect(() => {
    async function fetchBounties() {
      setLoading(true)
      setError(null)

      try {
        const params = new URLSearchParams({
          sort: sortBy,
          page: page.toString(),
          per_page: '20',
        })

        if (minReward !== null) {
          params.append('min_reward', minReward.toString())
        }

        const response = await apiClient.get<BountiesResponse>(`/bounties?${params}`)
        
        if (page === 1) {
          setBounties(response.data.data || [])
        } else {
          setBounties(prev => [...prev, ...(response.data.data || [])])
        }
        
        setHasMore(response.data.current_page < response.data.last_page)
      } catch (err) {
        console.error('Failed to fetch bounties:', err)
        setError('Failed to load bounties. Please try again.')
      } finally {
        setLoading(false)
      }
    }

    fetchBounties()
  }, [sortBy, minReward, page])

  const handleSortChange = (newSort: string) => {
    setSortBy(newSort)
    setPage(1)
  }

  const handleMinRewardChange = (value: number | null) => {
    setMinReward(value)
    setPage(1)
  }

  const handleRefresh = () => {
    setPage(1)
  }

  const minRewardOptions = [
    { value: null, label: 'Any' },
    { value: 50, label: '50+' },
    { value: 100, label: '100+' },
    { value: 250, label: '250+' },
    { value: 500, label: '500+' },
  ]

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gradient-to-b from-purple-950 via-slate-900 to-black">
        <AppHeader />
        
        <main className="max-w-4xl mx-auto px-4 py-6">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-3">
              <Link 
                href="/home" 
                className="p-2 -ml-2 hover:bg-purple-800/30 rounded-lg transition"
              >
                <ArrowLeft className="w-5 h-5 text-gray-400" />
              </Link>
              <div>
                <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                  <Target className="w-7 h-7 text-pink-500" />
                  Matchmaker Bounties
                </h1>
                <p className="text-sm text-gray-400">
                  Help singles find love and earn token rewards
                </p>
              </div>
            </div>
            <button
              onClick={handleRefresh}
              className="p-2 hover:bg-purple-800/30 rounded-lg transition"
              title="Refresh bounties"
            >
              <RefreshCw className={`w-5 h-5 text-gray-400 ${loading ? 'animate-spin' : ''}`} />
            </button>
          </div>

          <div className="mb-6 p-4 bg-gradient-to-r from-pink-600/20 to-purple-600/20 rounded-xl border border-pink-500/20">
            <div className="flex items-start gap-3">
              <Heart className="w-6 h-6 text-pink-400 flex-shrink-0 mt-0.5" />
              <div>
                <h3 className="font-semibold text-white mb-1">How Bounties Work</h3>
                <p className="text-sm text-gray-300">
                  Users looking for love post bounties with token rewards. Suggest a friend who matches their vibe - 
                  if they hit it off, you earn the tokens!
                </p>
              </div>
            </div>
          </div>

          <button
            onClick={() => setShowFilters(!showFilters)}
            className="w-full mb-4 flex items-center justify-between px-4 py-3 bg-slate-800/50 rounded-xl border border-purple-500/20"
          >
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5 text-purple-400" />
              <span className="font-medium text-gray-200">Filters</span>
              {minReward !== null && (
                <span className="px-2 py-0.5 bg-purple-500/20 text-purple-300 text-xs rounded-full">
                  {minReward}+ tokens
                </span>
              )}
            </div>
            <ChevronDown className={`w-5 h-5 text-gray-400 transition-transform ${showFilters ? 'rotate-180' : ''}`} />
          </button>

          {showFilters && (
            <div className="mb-6 p-4 bg-slate-800/50 rounded-xl border border-purple-500/20 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">Minimum Reward</label>
                <div className="flex flex-wrap gap-2">
                  {minRewardOptions.map(opt => (
                    <button
                      key={opt.value ?? 'any'}
                      onClick={() => handleMinRewardChange(opt.value)}
                      className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                        minReward === opt.value
                          ? 'bg-purple-500 text-white'
                          : 'bg-purple-500/20 text-purple-300 hover:bg-purple-500/30'
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">Sort By</label>
                <div className="flex flex-wrap gap-2">
                  {sortOptions.map(opt => (
                    <button
                      key={opt.value}
                      onClick={() => handleSortChange(opt.value)}
                      className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                        sortBy === opt.value
                          ? 'bg-purple-500 text-white'
                          : 'bg-purple-500/20 text-purple-300 hover:bg-purple-500/30'
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          )}

          <div className="flex items-center gap-4 mb-6 text-sm text-gray-400">
            <span className="flex items-center gap-1">
              <Target className="w-4 h-4" />
              {bounties.length} active bounties
            </span>
          </div>

          {loading && bounties.length === 0 ? (
            <div className="grid gap-4 sm:grid-cols-2">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="bg-slate-800/50 rounded-xl p-4 animate-pulse">
                  <div className="flex gap-3">
                    <div className="w-16 h-16 bg-slate-700 rounded-full" />
                    <div className="flex-1 space-y-2">
                      <div className="h-4 bg-slate-700 rounded w-3/4" />
                      <div className="h-3 bg-slate-700/50 rounded w-1/2" />
                    </div>
                  </div>
                  <div className="mt-4 h-10 bg-slate-700/50 rounded" />
                </div>
              ))}
            </div>
          ) : error ? (
            <div className="text-center py-12">
              <p className="text-red-400 mb-4">{error}</p>
              <button
                onClick={handleRefresh}
                className="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition"
              >
                Try Again
              </button>
            </div>
          ) : bounties.length === 0 ? (
            <div className="text-center py-12">
              <Users className="w-16 h-16 text-purple-500/50 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-white mb-2">No Active Bounties</h3>
              <p className="text-gray-400 mb-4">
                Check back later for new matchmaker opportunities!
              </p>
            </div>
          ) : (
            <>
              <div className="grid gap-4 sm:grid-cols-2">
                {bounties.map(bounty => {
                  const primaryPhoto = bounty.user.photos.find(p => p.is_primary) || bounty.user.photos[0]
                  const displayName = bounty.user.profile?.display_name || bounty.user.name
                  const timeLeft = getTimeRemaining(bounty.expires_at)
                  const isExpiringSoon = timeLeft.includes('h') || timeLeft.includes('soon')

                  return (
                    <Link
                      key={bounty.id}
                      href={`/bounty/${bounty.slug}`}
                      className="bg-slate-800/50 rounded-xl border border-purple-500/20 overflow-hidden hover:border-purple-500/40 hover:shadow-lg hover:shadow-purple-900/20 transition group"
                    >
                      <div className="p-4">
                        <div className="flex items-start gap-3 mb-3">
                          <div className="relative">
                            <div className="w-14 h-14 rounded-full overflow-hidden bg-slate-700 flex items-center justify-center">
                              {primaryPhoto ? (
                                <Image
                                  src={primaryPhoto.url} 
                                  alt={displayName}
                                  fill
                                  className="object-cover"
                                />
                              ) : (
                                <Users className="w-7 h-7 text-gray-500" />
                              )}
                            </div>
                            <div className="absolute -bottom-1 -right-1 bg-gradient-to-r from-yellow-500 to-amber-500 rounded-full p-1">
                              <Coins className="w-3.5 h-3.5 text-yellow-900" />
                            </div>
                          </div>
                          <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-white truncate group-hover:text-pink-300 transition">
                              {displayName}
                              {bounty.user.profile?.age && (
                                <span className="text-gray-400 font-normal">, {bounty.user.profile.age}</span>
                              )}
                            </h3>
                            <p className="text-sm text-gray-400">
                              {bounty.user.profile?.gender || 'Looking for love'}
                            </p>
                          </div>
                        </div>

                        {bounty.description && (
                          <p className="text-sm text-gray-300 line-clamp-2 mb-3">
                            {bounty.description}
                          </p>
                        )}

                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-2 bg-gradient-to-r from-yellow-500/20 to-amber-500/20 px-3 py-1.5 rounded-lg border border-yellow-500/30">
                            <Coins className="w-4 h-4 text-yellow-400" />
                            <span className="font-bold text-yellow-300">
                              {formatTokenReward(bounty.token_reward)}
                            </span>
                            <span className="text-yellow-400/80 text-sm">tokens</span>
                          </div>
                          
                          <span className={`flex items-center gap-1 text-xs ${isExpiringSoon ? 'text-red-400' : 'text-gray-500'}`}>
                            <Clock className="w-3.5 h-3.5" />
                            {timeLeft}
                          </span>
                        </div>
                      </div>

                      <div className="px-4 py-3 bg-purple-500/10 border-t border-purple-500/20 flex items-center justify-between">
                        <span className="text-sm text-purple-300">
                          Suggest a match
                        </span>
                        <Heart className="w-4 h-4 text-pink-400 group-hover:scale-110 transition-transform" />
                      </div>
                    </Link>
                  )
                })}
              </div>

              {hasMore && (
                <div className="mt-6 text-center">
                  <button
                    onClick={() => setPage(p => p + 1)}
                    disabled={loading}
                    className="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition disabled:opacity-50"
                  >
                    {loading ? 'Loading...' : 'Load More Bounties'}
                  </button>
                </div>
              )}
            </>
          )}

          <div className="mt-8 p-4 bg-gradient-to-r from-pink-600 to-purple-600 rounded-xl text-white">
            <div className="flex items-center gap-3">
              <Crown className="w-10 h-10" />
              <div className="flex-1">
                <h3 className="font-semibold">Looking for your match?</h3>
                <p className="text-sm text-pink-100">Create a bounty and let others help find your perfect match</p>
              </div>
              <Link
                href="/profile/bounty/create"
                className="px-4 py-2 bg-white text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition"
              >
                Create Bounty
              </Link>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
