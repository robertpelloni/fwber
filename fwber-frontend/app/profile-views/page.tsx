'use client'

import { useState, useEffect } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { useAuth } from '@/lib/auth-context'
import { 
  Eye, ArrowLeft, TrendingUp, Users, Calendar, Clock,
  ChevronRight, User, MapPin
} from 'lucide-react'
import Link from 'next/link'
import Image from 'next/image'

interface ProfileViewer {
  id: number
  viewer: {
    id: number
    name: string
    avatar_url: string | null
    age: number | null
    city: string | null
  }
  viewed_at: string
}

interface ViewStats {
  total_views: number
  today_views: number
  week_views: number
  month_views: number
  unique_viewers: number
}

export default function ProfileViewsPage() {
  const { user } = useAuth()
  const [viewers, setViewers] = useState<ProfileViewer[]>([])
  const [stats, setStats] = useState<ViewStats | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!user?.id) return

    Promise.all([
      apiClient.get<ProfileViewer[]>(`/profile/${user.id}/views`),
      apiClient.get<ViewStats>(`/profile/${user.id}/view-stats`)
    ])
      .then(([viewersRes, statsRes]) => {
        setViewers(viewersRes.data)
        setStats(statsRes.data)
      })
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [user?.id])

  const formatTimeAgo = (dateStr: string) => {
    const date = new Date(dateStr)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / (1000 * 60))
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`
    return date.toLocaleDateString()
  }

  if (loading) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
          <AppHeader />
          <div className="max-w-4xl mx-auto px-4 py-8">
            <div className="animate-pulse space-y-6">
              <div className="h-8 bg-gray-200 dark:bg-gray-800 rounded w-1/3" />
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {[1, 2, 3, 4].map(i => (
                  <div key={i} className="h-24 bg-gray-200 dark:bg-gray-800 rounded-xl" />
                ))}
              </div>
              <div className="space-y-4">
                {[1, 2, 3, 4, 5].map(i => (
                  <div key={i} className="h-20 bg-gray-200 dark:bg-gray-800 rounded-xl" />
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
        
        <main className="max-w-4xl mx-auto px-4 py-8">
          <div className="mb-8">
            <Link 
              href="/dashboard" 
              className="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4 transition-colors"
            >
              <ArrowLeft className="w-4 h-4" />
              Back to Dashboard
            </Link>
            <div className="flex items-center gap-4">
              <div className="p-3 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-lg">
                <Eye className="w-8 h-8 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Who Viewed You</h1>
                <p className="text-gray-600 dark:text-gray-400">See who&apos;s checking out your profile</p>
              </div>
            </div>
          </div>

          {stats && (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
              <StatCard 
                icon={<Eye className="w-5 h-5" />}
                label="Total Views"
                value={stats.total_views}
                color="blue"
              />
              <StatCard 
                icon={<Calendar className="w-5 h-5" />}
                label="Today"
                value={stats.today_views}
                color="green"
              />
              <StatCard 
                icon={<TrendingUp className="w-5 h-5" />}
                label="This Week"
                value={stats.week_views}
                color="purple"
              />
              <StatCard 
                icon={<Users className="w-5 h-5" />}
                label="Unique Viewers"
                value={stats.unique_viewers}
                color="orange"
              />
            </div>
          )}

          <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div className="p-4 border-b border-gray-200 dark:border-gray-800">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Users className="w-5 h-5 text-blue-500" />
                Recent Visitors
              </h2>
              <p className="text-sm text-gray-500 dark:text-gray-400">
                {viewers.length > 0 ? `${viewers.length} recent visitors` : 'No visitors yet'}
              </p>
            </div>

            {viewers.length > 0 ? (
              <div className="divide-y divide-gray-100 dark:divide-gray-800">
                {viewers.map((view) => (
                  <Link
                    key={view.id}
                    href={`/profile/${view.viewer.id}`}
                    className="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group"
                  >
                    <div className="relative">
                      {view.viewer.avatar_url ? (
                        <Image
                          src={view.viewer.avatar_url}
                          alt={view.viewer.name}
                          width={56}
                          height={56}
                          className="w-14 h-14 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-800"
                        />
                      ) : (
                        <div className="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                          <User className="w-6 h-6 text-white" />
                        </div>
                      )}
                    </div>

                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <h3 className="font-semibold text-gray-900 dark:text-white truncate">
                          {view.viewer.name}
                        </h3>
                        {view.viewer.age && (
                          <span className="text-sm text-gray-500 dark:text-gray-400">
                            {view.viewer.age}
                          </span>
                        )}
                      </div>
                      {view.viewer.city && (
                        <p className="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                          <MapPin className="w-3 h-3" />
                          {view.viewer.city}
                        </p>
                      )}
                      <p className="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-1">
                        <Clock className="w-3 h-3" />
                        Viewed {formatTimeAgo(view.viewed_at)}
                      </p>
                    </div>

                    <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" />
                  </Link>
                ))}
              </div>
            ) : (
              <div className="p-12 text-center">
                <div className="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                  <Eye className="w-8 h-8 text-gray-400" />
                </div>
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  No profile views yet
                </h3>
                <p className="text-gray-600 dark:text-gray-400 mb-4">
                  Complete your profile and stay active to attract more visitors!
                </p>
                <Link
                  href="/profile/edit"
                  className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Complete Your Profile
                </Link>
              </div>
            )}
          </div>

          <div className="mt-8 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-6">
            <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
              <TrendingUp className="w-5 h-5 text-blue-600" />
              Tips to Get More Views
            </h3>
            <ul className="space-y-2 text-sm text-gray-700 dark:text-gray-300">
              <li className="flex items-start gap-2">
                <span className="text-blue-500">•</span>
                Add more photos to your profile
              </li>
              <li className="flex items-start gap-2">
                <span className="text-blue-500">•</span>
                Write an engaging bio that shows your personality
              </li>
              <li className="flex items-start gap-2">
                <span className="text-blue-500">•</span>
                Be active on Local Pulse and participate in discussions
              </li>
              <li className="flex items-start gap-2">
                <span className="text-blue-500">•</span>
                Use Boost to appear at the top of discovery feeds
              </li>
            </ul>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}

function StatCard({ 
  icon, 
  label, 
  value, 
  color 
}: { 
  icon: React.ReactNode
  label: string
  value: number
  color: 'blue' | 'green' | 'purple' | 'orange'
}) {
  const colorClasses = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
  }

  return (
    <div className="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
      <div className={`w-10 h-10 rounded-lg ${colorClasses[color]} flex items-center justify-center mb-3`}>
        {icon}
      </div>
      <div className="text-2xl font-bold text-gray-900 dark:text-white">{value}</div>
      <div className="text-sm text-gray-500 dark:text-gray-400">{label}</div>
    </div>
  )
}
