'use client'

import { useState, useEffect } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import { apiClient } from '@/lib/api/client'
import VouchLeaderboard from '@/components/VouchLeaderboard'
import { Trophy, Users, TrendingUp } from 'lucide-react'


interface LeaderboardData {
  top_holders: Array<{
    name: string
    balance: string
    joined: string
  }>
  top_referrers: Array<{
    name: string
    referrals: number
  }>
  top_wingmen: Array<{
    name: string
    assists: number
  }>
  top_vouched: Array<{
    name: string
    vouches: number
    breakdown?: {
      safe: number
      fun: number
      hot: number
    }
  }>
  top_streaks: Array<{
    name: string
    streak: number
  }>
}

export default function LeaderboardPage() {
  const [data, setData] = useState<LeaderboardData | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    apiClient.get<LeaderboardData>('/leaderboard')
      .then(res => setData(res.data))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="p-8 text-center">Loading leaderboard...</div>
  if (!data) return null

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">Community Leaderboard</h1>

          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
            {/* Top Holders */}
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div className="p-6 bg-gradient-to-r from-yellow-500 to-orange-500 text-white flex items-center gap-3">
                <Trophy className="w-6 h-6" />
                <h2 className="text-xl font-bold">Top Token Holders</h2>
              </div>
              <div className="divide-y divide-gray-200 dark:divide-gray-700">
                {data.top_holders.map((user, index) => (
                  <div key={index} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className={`w-8 h-8 flex items-center justify-center rounded-full font-bold ${
                        index === 0 ? 'bg-yellow-100 text-yellow-700' :
                        index === 1 ? 'bg-gray-100 text-gray-700' :
                        index === 2 ? 'bg-orange-100 text-orange-700' :
                        'text-gray-500'
                      }`}>
                        {index + 1}
                      </span>
                      <div>
                        <p className="font-medium text-gray-900 dark:text-white">{user.name}</p>
                        <p className="text-xs text-gray-500">Joined {user.joined}</p>
                      </div>
                    </div>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {parseFloat(user.balance).toLocaleString()} FWB
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Top Referrers */}
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div className="p-6 bg-gradient-to-r from-blue-500 to-cyan-500 text-white flex items-center gap-3">
                <Users className="w-6 h-6" />
                <h2 className="text-xl font-bold">Top Referrers</h2>
              </div>
              <div className="divide-y divide-gray-200 dark:divide-gray-700">
                {data.top_referrers.map((user, index) => (
                  <div key={index} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className={`w-8 h-8 flex items-center justify-center rounded-full font-bold ${
                        index === 0 ? 'bg-yellow-100 text-yellow-700' :
                        index === 1 ? 'bg-gray-100 text-gray-700' :
                        index === 2 ? 'bg-orange-100 text-orange-700' :
                        'text-gray-500'
                      }`}>
                        {index + 1}
                      </span>
                      <p className="font-medium text-gray-900 dark:text-white">{user.name}</p>
                    </div>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {user.referrals} invites
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Top Wingmen */}
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div className="p-6 bg-gradient-to-r from-purple-500 to-pink-500 text-white flex items-center gap-3">
                <TrendingUp className="w-6 h-6" />
                <h2 className="text-xl font-bold">Top Wingmen</h2>
              </div>
              <div className="divide-y divide-gray-200 dark:divide-gray-700">
                {data.top_wingmen?.map((user, index) => (
                  <div key={index} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className={`w-8 h-8 flex items-center justify-center rounded-full font-bold ${
                        index === 0 ? 'bg-yellow-100 text-yellow-700' :
                        index === 1 ? 'bg-gray-100 text-gray-700' :
                        index === 2 ? 'bg-orange-100 text-orange-700' :
                        'text-gray-500'
                      }`}>
                        {index + 1}
                      </span>
                      <p className="font-medium text-gray-900 dark:text-white">{user.name}</p>
                    </div>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {user.assists} matches
                    </span>
                  </div>
                ))}
                {(!data.top_wingmen || data.top_wingmen.length === 0) && (
                  <div className="p-8 text-center text-gray-500">
                    No wingmen yet. Be the first!
                  </div>
                )}
              </div>
            </div>

            {/* Top Vouched */}
            <VouchLeaderboard data={data.top_vouched} />

            {/* Top Streaks */}
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div className="p-6 bg-gradient-to-r from-green-500 to-emerald-500 text-white flex items-center gap-3">
                <TrendingUp className="w-6 h-6" />
                <h2 className="text-xl font-bold">Longest Streaks</h2>
              </div>
              <div className="divide-y divide-gray-200 dark:divide-gray-700">
                {data.top_streaks?.map((user, index) => (
                  <div key={index} className="p-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className={`w-8 h-8 flex items-center justify-center rounded-full font-bold ${
                        index === 0 ? 'bg-yellow-100 text-yellow-700' :
                        index === 1 ? 'bg-gray-100 text-gray-700' :
                        index === 2 ? 'bg-orange-100 text-orange-700' :
                        'text-gray-500'
                      }`}>
                        {index + 1}
                      </span>
                      <p className="font-medium text-gray-900 dark:text-white">{user.name}</p>
                    </div>
                    <div className="flex items-center gap-1 font-bold text-gray-900 dark:text-white">
                      {user.streak} <span className="text-sm font-normal text-gray-500">days</span>
                    </div>
                  </div>
                ))}
                {(!data.top_streaks || data.top_streaks.length === 0) && (
                  <div className="p-8 text-center text-gray-500">
                    No active streaks yet.
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </ProtectedRoute>
  )
}

