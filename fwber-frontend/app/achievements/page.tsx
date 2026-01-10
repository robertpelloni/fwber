'use client'

import { useState, useEffect, useMemo } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { 
  Trophy, Flame, Heart, Eye, MessageCircle, Users, Zap, UserCheck, 
  Lock, CheckCircle2, Award, Star, Target, Sparkles, ArrowLeft,
  TrendingUp, Gift, Share2
} from 'lucide-react'
import Link from 'next/link'

interface Achievement {
  id: number
  name: string
  description: string
  icon: string
  category: string
  reward_tokens: number
  is_unlocked: boolean
  unlocked_at: string | null
  is_hidden: boolean
}

interface AchievementsResponse {
  achievements: Achievement[]
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  'trophy': Trophy,
  'flame': Flame,
  'heart': Heart,
  'eye': Eye,
  'message-circle': MessageCircle,
  'users': Users,
  'zap': Zap,
  'user-check': UserCheck,
  'award': Award,
  'star': Star,
  'target': Target,
  'sparkles': Sparkles,
  'trending-up': TrendingUp,
  'gift': Gift,
  'share': Share2,
}

const categoryConfig: Record<string, { label: string; color: string; gradient: string; icon: React.ComponentType<{ className?: string }> }> = {
  all: { label: 'All', color: 'gray', gradient: 'from-gray-500 to-gray-600', icon: Trophy },
  onboarding: { label: 'Getting Started', color: 'orange', gradient: 'from-orange-500 to-amber-500', icon: Star },
  social: { label: 'Social', color: 'blue', gradient: 'from-blue-500 to-cyan-500', icon: Users },
  activity: { label: 'Activity', color: 'green', gradient: 'from-green-500 to-emerald-500', icon: Zap },
  growth: { label: 'Growth', color: 'purple', gradient: 'from-purple-500 to-pink-500', icon: TrendingUp },
  viral: { label: 'Viral', color: 'pink', gradient: 'from-pink-500 to-rose-500', icon: Share2 },
}

export default function AchievementsPage() {
  const [achievements, setAchievements] = useState<Achievement[]>([])
  const [loading, setLoading] = useState(true)
  const [selectedCategory, setSelectedCategory] = useState('all')

  useEffect(() => {
    apiClient.get<AchievementsResponse>('/achievements')
      .then(res => setAchievements(res.data.achievements))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  // Filter out hidden achievements that aren't unlocked
  const visibleAchievements = useMemo(() => 
    achievements.filter(a => !a.is_hidden || a.is_unlocked),
    [achievements]
  )

  // Filter by category
  const filteredAchievements = useMemo(() => 
    selectedCategory === 'all' 
      ? visibleAchievements 
      : visibleAchievements.filter(a => a.category === selectedCategory),
    [visibleAchievements, selectedCategory]
  )

  // Stats
  const stats = useMemo(() => {
    const unlocked = visibleAchievements.filter(a => a.is_unlocked)
    const totalTokens = unlocked.reduce((sum, a) => sum + a.reward_tokens, 0)
    const categories = [...new Set(visibleAchievements.map(a => a.category))]
    const categoryProgress = categories.reduce((acc, cat) => {
      const catAchievements = visibleAchievements.filter(a => a.category === cat)
      const catUnlocked = catAchievements.filter(a => a.is_unlocked)
      acc[cat] = { total: catAchievements.length, unlocked: catUnlocked.length }
      return acc
    }, {} as Record<string, { total: number; unlocked: number }>)
    
    return {
      total: visibleAchievements.length,
      unlocked: unlocked.length,
      totalTokens,
      categoryProgress,
    }
  }, [visibleAchievements])

  if (loading) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
          <AppHeader />
          <div className="max-w-6xl mx-auto px-4 py-8">
            <div className="animate-pulse space-y-6">
              <div className="h-8 bg-gray-200 dark:bg-gray-800 rounded w-1/3" />
              <div className="grid grid-cols-3 gap-4">
                {[1, 2, 3].map(i => (
                  <div key={i} className="h-24 bg-gray-200 dark:bg-gray-800 rounded-xl" />
                ))}
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {[1, 2, 3, 4, 5, 6].map(i => (
                  <div key={i} className="h-32 bg-gray-200 dark:bg-gray-800 rounded-xl" />
                ))}
              </div>
            </div>
          </div>
        </div>
      </ProtectedRoute>
    )
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader />
        
        <main className="max-w-6xl mx-auto px-4 py-8">
          {/* Header */}
          <div className="mb-8">
            <Link 
              href="/dashboard" 
              className="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4 transition-colors"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to Dashboard
            </Link>
            <div className="flex items-center gap-4">
              <div className="p-3 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl shadow-lg">
                <Trophy className="w-8 h-8 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Achievements</h1>
                <p className="text-gray-600 dark:text-gray-400">Track your progress and earn rewards</p>
              </div>
            </div>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            {/* Progress */}
            <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
              <div className="flex items-center justify-between mb-4">
                <span className="text-sm font-medium text-gray-600 dark:text-gray-400">Progress</span>
                <Award className="w-5 h-5 text-purple-500" />
              </div>
              <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {stats.unlocked}/{stats.total}
              </div>
              <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  className="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full transition-all duration-500"
                  style={{ width: `${(stats.unlocked / stats.total) * 100}%` }}
                />
              </div>
              <p className="text-xs text-gray-500 mt-2">
                {Math.round((stats.unlocked / stats.total) * 100)}% complete
              </p>
            </div>

            {/* Tokens Earned */}
            <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
              <div className="flex items-center justify-between mb-4">
                <span className="text-sm font-medium text-gray-600 dark:text-gray-400">Tokens Earned</span>
                <Sparkles className="w-5 h-5 text-yellow-500" />
              </div>
              <div className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {stats.totalTokens.toLocaleString()}
              </div>
              <div className="flex items-center gap-1 text-yellow-600 dark:text-yellow-400">
                <Trophy className="w-4 h-4" />
                <span className="text-sm font-medium">FWB Tokens</span>
              </div>
            </div>

            {/* Recent Unlock */}
            <div className="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-2xl border border-yellow-200 dark:border-yellow-800 p-6">
              <div className="flex items-center justify-between mb-4">
                <span className="text-sm font-medium text-yellow-700 dark:text-yellow-400">Latest Achievement</span>
                <CheckCircle2 className="w-5 h-5 text-yellow-500" />
              </div>
              {visibleAchievements.filter(a => a.is_unlocked).length > 0 ? (
                <>
                  <div className="text-lg font-bold text-gray-900 dark:text-white mb-1">
                    {visibleAchievements.filter(a => a.is_unlocked).sort((a, b) => 
                      new Date(b.unlocked_at || 0).getTime() - new Date(a.unlocked_at || 0).getTime()
                    )[0]?.name}
                  </div>
                  <p className="text-xs text-gray-600 dark:text-gray-400">Keep up the great work!</p>
                </>
              ) : (
                <>
                  <div className="text-lg font-bold text-gray-900 dark:text-white mb-1">None yet</div>
                  <p className="text-xs text-gray-600 dark:text-gray-400">Complete your first achievement!</p>
                </>
              )}
            </div>
          </div>

          {/* Category Tabs */}
          <div className="flex flex-wrap gap-2 mb-6">
            {Object.entries(categoryConfig).map(([key, config]) => {
              const progress = key === 'all' 
                ? { unlocked: stats.unlocked, total: stats.total }
                : stats.categoryProgress[key] || { unlocked: 0, total: 0 }
              const CategoryIcon = config.icon
              
              return (
                <button
                  key={key}
                  onClick={() => setSelectedCategory(key)}
                  className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all ${
                    selectedCategory === key
                      ? `bg-gradient-to-r ${config.gradient} text-white shadow-md`
                      : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                  }`}
                >
                  <CategoryIcon className="w-4 h-4" />
                  {config.label}
                  {progress.total > 0 && (
                    <span className={`text-xs ${
                      selectedCategory === key ? 'text-white/80' : 'text-gray-500'
                    }`}>
                      {progress.unlocked}/{progress.total}
                    </span>
                  )}
                </button>
              )
            })}
          </div>

          {/* Achievements Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredAchievements.map((achievement) => {
              const Icon = iconMap[achievement.icon] || Trophy
              const catConfig = categoryConfig[achievement.category] || categoryConfig.all
              
              return (
                <div
                  key={achievement.id}
                  className={`relative overflow-hidden rounded-2xl border transition-all duration-300 ${
                    achievement.is_unlocked
                      ? 'bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-yellow-200 dark:border-yellow-800 shadow-lg'
                      : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 opacity-75 hover:opacity-100'
                  }`}
                >
                  {/* Unlocked Badge */}
                  {achievement.is_unlocked && (
                    <div className="absolute top-3 right-3">
                      <div className="flex items-center gap-1 px-2 py-1 bg-yellow-500 text-white text-xs font-medium rounded-full">
                        <CheckCircle2 className="w-3 h-3" />
                        Unlocked
                      </div>
                    </div>
                  )}

                  <div className="p-5">
                    <div className="flex items-start gap-4">
                      {/* Icon */}
                      <div className={`p-3 rounded-xl ${
                        achievement.is_unlocked
                          ? `bg-gradient-to-br ${catConfig.gradient} shadow-md`
                          : 'bg-gray-100 dark:bg-gray-800'
                      }`}>
                        {achievement.is_unlocked ? (
                          <Icon className="w-6 h-6 text-white" />
                        ) : (
                          <Lock className="w-6 h-6 text-gray-400 dark:text-gray-500" />
                        )}
                      </div>

                      {/* Content */}
                      <div className="flex-1 min-w-0">
                        <h3 className={`font-semibold mb-1 ${
                          achievement.is_unlocked 
                            ? 'text-gray-900 dark:text-white' 
                            : 'text-gray-500 dark:text-gray-400'
                        }`}>
                          {achievement.name}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                          {achievement.description}
                        </p>
                      </div>
                    </div>

                    {/* Footer */}
                    <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                          achievement.is_unlocked
                            ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
                        }`}>
                          {catConfig.label}
                        </span>
                      </div>
                      
                      {achievement.reward_tokens > 0 && (
                        <div className={`flex items-center gap-1 text-sm font-medium ${
                          achievement.is_unlocked
                            ? 'text-yellow-600 dark:text-yellow-400'
                            : 'text-gray-400 dark:text-gray-500'
                        }`}>
                          <Trophy className="w-4 h-4" />
                          +{achievement.reward_tokens} Tokens
                        </div>
                      )}
                    </div>

                    {/* Unlock Date */}
                    {achievement.is_unlocked && achievement.unlocked_at && (
                      <p className="mt-2 text-xs text-gray-500 dark:text-gray-500">
                        Unlocked {new Date(achievement.unlocked_at).toLocaleDateString()}
                      </p>
                    )}
                  </div>

                  {/* Shine effect for unlocked */}
                  {achievement.is_unlocked && (
                    <div className="absolute inset-0 pointer-events-none">
                      <div className="absolute -inset-full animate-[shimmer_3s_ease-in-out_infinite] bg-gradient-to-r from-transparent via-white/10 to-transparent skew-x-12" />
                    </div>
                  )}
                </div>
              )
            })}
          </div>

          {/* Empty State */}
          {filteredAchievements.length === 0 && (
            <div className="text-center py-16">
              <div className="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                <Trophy className="w-8 h-8 text-gray-400" />
              </div>
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                No achievements in this category
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                Try selecting a different category or keep using fwber to unlock achievements!
              </p>
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}
