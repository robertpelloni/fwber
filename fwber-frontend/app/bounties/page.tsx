'use client';

import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Info, Target, Coins, ShieldAlert } from 'lucide-react';
import { toast } from 'react-toastify';

export default function BountiesPage() {
    const [bounties, setBounties] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchBounties = async () => {
            try {
                const res = await fetch('/api/bounties');
                if (!res.ok) throw new Error('Failed to fetch bounties');
                const data = await res.json();
                setBounties(data.bounties || []);
            } catch (e: any) {
                toast.error(e.message || 'Error loading bounties');
            } finally {
                setLoading(false);
            }
        };
        fetchBounties();
    }, []);

    return (
        <div className="container mx-auto p-4 md:p-8 max-w-5xl space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                    <Target className="w-8 h-8 text-primary" />
                    Community Bounties
                </h1>
                <Button>Post a Bounty</Button>
            </div>

            <Card className="bg-white shadow-sm border border-gray-100 mb-8">
                <CardHeader>
                    <CardTitle className="text-xl flex items-center gap-2">
                        Earn Tokens
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Info className="w-4 h-4 text-gray-400 cursor-help" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Complete requests from other users or merchants to earn tokens.</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </CardTitle>
                    <CardDescription>
                        Help out the community and get rewarded.
                    </CardDescription>
                </CardHeader>
            </Card>

            {loading ? (
                <div className="flex justify-center p-12"><div className="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full" /></div>
            ) : bounties.length === 0 ? (
                <div className="text-center p-12 bg-gray-50 rounded-lg border border-gray-200">
                    <ShieldAlert className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-700">No active bounties</h3>
                    <p className="text-gray-500">Check back later or post your own request to the community!</p>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {bounties.map((bounty) => (
                        <Card key={bounty.id} className="hover:shadow-md transition-shadow">
                            <CardContent className="p-6">
                                <div className="flex justify-between items-start mb-4">
                                    <h3 className="font-bold text-lg">{bounty.title}</h3>
                                    <span className="flex items-center gap-1 font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-md text-sm">
                                        <Coins className="w-4 h-4" /> {bounty.rewardTokens}
                                    </span>
                                <p className="text-gray-600 text-sm mb-4">{bounty.description}</p>
                                <div className="flex justify-between items-center text-xs text-gray-500">
                                    <span>Posted by {bounty.author}</span>
                                    <Button size="sm" variant="outline">Claim Bounty</Button>
                            </CardContent>
                    ))}
            )}
    );

'use client'

import { useState, useEffect } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import CreateBountyModal from '@/components/CreateBountyModal'
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
    birthdate?: string | null
    gender: string
  } | null
  photos: {
    url: string
    is_primary: boolean
  }[]
}

interface Bounty {
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

  const [bounties, setBounties] = useState<Bounty[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [sortBy, setSortBy] = useState('reward')
  const [minReward, setMinReward] = useState<number | null>(null)
  const [showFilters, setShowFilters] = useState(false)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)
  const [refreshKey, setRefreshKey] = useState(0)

    async function fetchBounties() {
      setLoading(true)
      setError(null)

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
        setLoading(false)
      }
    }

    fetchBounties()
  }, [sortBy, minReward, page, refreshKey])

  const handleSortChange = (newSort: string) => {
    setSortBy(newSort)
    setPage(1)
  }

  const handleMinRewardChange = (value: number | null) => {
    setMinReward(value)
  }

  const handleRefresh = () => {
    setRefreshKey(prev => prev + 1)
  }

  const minRewardOptions = [
    { value: null, label: 'Any' },
    { value: 50, label: '50+' },
    { value: 100, label: '100+' },
    { value: 250, label: '250+' },
    { value: 500, label: '500+' },
  ]

    <ProtectedRoute>
      <div className="min-h-screen bg-gradient-to-b from-purple-950 via-slate-900 to-black transition-colors duration-300">
        <AppHeader />
        
        <main className="max-w-4xl mx-auto px-4 py-6">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-3">
              <Link 
                href="/dashboard" 
                className="p-2 -ml-2 hover:bg-purple-800/30 dark:hover:bg-purple-900/40 rounded-lg transition"
              >
                <ArrowLeft className="w-5 h-5 text-gray-400 dark:text-gray-500" />
              </Link>
              <div>
                <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                  <Target className="w-7 h-7 text-pink-500 dark:text-pink-400" />
                  Matchmaker Bounties
                <p className="text-sm text-gray-400 dark:text-gray-500">
                  Help singles find love and earn token rewards
                </p>
            <button
              onClick={handleRefresh}
              className="p-2 hover:bg-purple-800/30 dark:hover:bg-purple-900/40 rounded-lg transition"
              title="Refresh bounties"
              <RefreshCw className={`w-5 h-5 text-gray-400 dark:text-gray-500 ${loading ? 'animate-spin' : ''}`} />
            </button>

          <div className="mb-6 p-4 bg-gradient-to-r from-pink-600/20 to-purple-600/20 rounded-xl border border-pink-500/20 dark:border-pink-500/10">
            <div className="flex items-start gap-3">
              <Heart className="w-6 h-6 text-pink-400 dark:text-pink-500 flex-shrink-0 mt-0.5" />
                <h3 className="font-semibold text-white mb-1">How Bounties Work</h3>
                <p className="text-sm text-gray-300 dark:text-gray-400">
                  Users looking for love post bounties with token rewards. Suggest a friend who matches their vibe - 
                  if they hit it off, you earn the tokens!

            onClick={() => setShowFilters(!showFilters)}
            className="w-full mb-4 flex items-center justify-between px-4 py-3 bg-slate-800/50 dark:bg-slate-900/60 rounded-xl border border-purple-500/20 dark:border-purple-500/10"
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5 text-purple-400 dark:text-purple-500" />
              <span className="font-medium text-gray-200">Filters</span>
              {minReward !== null && (
                <span className="px-2 py-0.5 bg-purple-500/20 dark:bg-purple-500/30 text-purple-300 dark:text-purple-400 text-xs rounded-full">
                  {minReward}+ tokens
            <ChevronDown className={`w-5 h-5 text-gray-400 dark:text-gray-600 transition-transform ${showFilters ? 'rotate-180' : ''}`} />

          {showFilters && (
            <div className="mb-6 p-4 bg-slate-800/50 dark:bg-slate-900/60 rounded-xl border border-purple-500/20 dark:border-purple-500/10 space-y-4">
                <label className="block text-sm font-medium text-gray-300 dark:text-gray-400 mb-2">Minimum Reward</label>
                <div className="flex flex-wrap gap-2">
                  {minRewardOptions.map(opt => (
                      key={opt.value ?? 'any'}
                      onClick={() => handleMinRewardChange(opt.value)}
                      className={`px-3 py-1.5 rounded-full text-sm font-medium transition ${
                        minReward === opt.value
                          ? 'bg-purple-500 dark:bg-purple-600 text-white'
                          : 'bg-purple-500/20 dark:bg-purple-500/10 text-purple-300 dark:text-purple-400 hover:bg-purple-500/30'
                      }`}
                      {opt.label}

                <label className="block text-sm font-medium text-gray-300 dark:text-gray-400 mb-2">Sort By</label>
                  {sortOptions.map(opt => (
                      key={opt.value}
                      onClick={() => handleSortChange(opt.value)}
                        sortBy === opt.value

          <div className="flex items-center gap-4 mb-6 text-sm text-gray-400 dark:text-gray-500">
            <span className="flex items-center gap-1">
              <Target className="w-4 h-4" />
              {bounties.length} active bounties

          {loading && bounties.length === 0 ? (
            <div className="grid gap-4 sm:grid-cols-2">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="bg-slate-800/50 dark:bg-slate-900/60 rounded-xl p-4 animate-pulse">
                  <div className="flex gap-3">
                    <div className="w-16 h-16 bg-slate-700 dark:bg-slate-800 rounded-full" />
                    <div className="flex-1 space-y-2">
                      <div className="h-4 bg-slate-700 dark:bg-slate-800 rounded w-3/4" />
                      <div className="h-3 bg-slate-700/50 dark:bg-slate-800/50 rounded w-1/2" />
                  <div className="mt-4 h-10 bg-slate-700/50 dark:bg-slate-800/50 rounded" />
          ) : error ? (
            <div className="text-center py-12">
              <p className="text-red-400 dark:text-red-500 mb-4">{error}</p>
                className="px-4 py-2 bg-purple-500 dark:bg-purple-600 text-white rounded-lg hover:bg-purple-600 dark:hover:bg-purple-700 transition"
                Try Again
              <Users className="w-16 h-16 text-purple-500/50 dark:text-purple-600/40 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-white mb-2">No Active Bounties</h3>
              <p className="text-gray-400 dark:text-gray-500 mb-4">
                Check back later for new matchmaker opportunities!
            <>
                {bounties.map(bounty => {
                  const photos = bounty.user.photos ?? []
                  const primaryPhoto = photos.find(p => p.is_primary) || photos[0]
                  const displayName = bounty.user.profile?.display_name || bounty.user.name
                  const timeLeft = getTimeRemaining(bounty.expires_at)
                  const isExpiringSoon = timeLeft && (timeLeft.includes('h') || timeLeft.includes('soon'))

                      key={bounty.id}
                      href={`/bounty/${bounty.slug}`}
                      className="bg-slate-800/50 dark:bg-slate-900/60 rounded-xl border border-purple-500/20 dark:border-purple-500/10 overflow-hidden hover:border-purple-500/40 dark:hover:border-purple-500/30 hover:shadow-lg hover:shadow-purple-900/20 transition group"
                      <div className="p-4">
                        <div className="flex items-start gap-3 mb-3">
                          <div className="relative">
                            <div className="w-14 h-14 rounded-full overflow-hidden bg-slate-700 dark:bg-slate-800 flex items-center justify-center">
                              {primaryPhoto ? (
                                <Image
                                  src={primaryPhoto.url} 
                                  alt={displayName}
                                  fill
                                  className="object-cover"
                                />
                                <Users className="w-7 h-7 text-gray-500 dark:text-gray-600" />
                            <div className="absolute -bottom-1 -right-1 bg-gradient-to-r from-yellow-500 to-amber-500 rounded-full p-1">
                              <Coins className="w-3.5 h-3.5 text-yellow-900 dark:text-yellow-950" />
                          <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-white truncate group-hover:text-pink-300 dark:group-hover:text-pink-400 transition">
                              {displayName}
                              {bounty.user.profile?.age && (
                                <span className="text-gray-400 dark:text-gray-500 font-normal">, {bounty.user.profile.age}</span>
                            </h3>
                              {bounty.user.profile?.gender || 'Looking for love'}

                        {bounty.description && (
                          <p className="text-sm text-gray-300 dark:text-gray-400 line-clamp-2 mb-3">
                            {bounty.description}

                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-2 bg-gradient-to-r from-yellow-500/20 to-amber-500/20 px-3 py-1.5 rounded-lg border border-yellow-500/30 dark:border-yellow-500/20">
                            <Coins className="w-4 h-4 text-yellow-400 dark:text-yellow-500" />
                            <span className="font-bold text-yellow-300 dark:text-yellow-400">
                              {formatTokenReward(bounty.token_reward)}
                            <span className="text-yellow-400/80 dark:text-yellow-500/70 text-sm">tokens</span>
                          
                          <span className={`flex items-center gap-1 text-xs ${isExpiringSoon ? 'text-red-400 dark:text-red-500' : 'text-gray-500 dark:text-gray-600'}`}>
                            <Clock className="w-3.5 h-3.5" />
                            {timeLeft}

                      <div className="px-4 py-3 bg-purple-500/10 dark:bg-purple-900/20 border-t border-purple-500/20 dark:border-purple-500/10 flex items-center justify-between">
                        <span className="text-sm text-purple-300 dark:text-purple-400">
                          Suggest a match
                        <Heart className="w-4 h-4 text-pink-400 dark:text-pink-500 group-hover:scale-110 transition-transform" />
                  )
                })}

              {hasMore && (
                <div className="mt-6 text-center">
                    onClick={() => setPage(p => p + 1)}
                    disabled={loading}
                    className="px-6 py-2 bg-purple-500 dark:bg-purple-600 text-white rounded-lg hover:bg-purple-600 dark:hover:bg-purple-700 transition disabled:opacity-50"
                    {loading ? 'Loading...' : 'Load More Bounties'}
            </>

          <div className="mt-8 p-4 bg-gradient-to-r from-pink-600 to-purple-600 dark:from-pink-700 dark:to-purple-700 rounded-xl text-white">
              <Crown className="w-10 h-10" />
              <div className="flex-1">
                <h3 className="font-semibold">Looking for your match?</h3>
                <p className="text-sm text-pink-100 dark:text-pink-200">Create a bounty and let others help find your perfect match</p>
              <CreateBountyModal
                triggerLabel="Create Bounty"
                triggerClassName="shrink-0 border-white/30 bg-white text-purple-600 hover:bg-purple-50 hover:text-purple-700 dark:bg-gray-100 dark:text-purple-800 dark:hover:bg-gray-200"
        </main>
    </ProtectedRoute>
}
