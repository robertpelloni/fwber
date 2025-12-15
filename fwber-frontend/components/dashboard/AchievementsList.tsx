'use client'

import { useEffect, useState } from 'react'
import { Award, Lock, CheckCircle2, Trophy, Flame, Heart, Eye, MessageCircle, Users, Zap, UserCheck } from 'lucide-react'
import { apiClient } from '@/lib/api/client'

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

const iconMap: Record<string, any> = {
  'trophy': Trophy,
  'flame': Flame,
  'heart': Heart,
  'eye': Eye,
  'message-circle': MessageCircle,
  'users': Users,
  'zap': Zap,
  'user-check': UserCheck,
}

export function AchievementsList() {
  const [achievements, setAchievements] = useState<Achievement[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    apiClient.get<AchievementsResponse>('/achievements')
      .then(res => {
        setAchievements(res.data.achievements)
        setIsLoading(false)
      })
      .catch(err => {
        console.error('Failed to fetch achievements', err)
        setIsLoading(false)
      })
  }, [])

  if (isLoading) {
    return <div className="animate-pulse space-y-4">
      {[1, 2, 3].map(i => (
        <div key={i} className="h-16 bg-gray-100 dark:bg-gray-800 rounded-lg" />
      ))}
    </div>
  }

  return (
    <div className="space-y-3">
      {achievements.map((achievement) => {
        const Icon = iconMap[achievement.icon] || Trophy
        
        if (achievement.is_hidden && !achievement.is_unlocked) {
          return null
        }

        return (
          <div 
            key={achievement.id} 
            className={`flex items-start gap-3 p-3 rounded-lg border transition-all duration-300 ${
              achievement.is_unlocked 
                ? 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800' 
                : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 opacity-70'
            }`}
          >
            <div className={`p-2 rounded-full ${
              achievement.is_unlocked 
                ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400' 
                : 'bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500'
            }`}>
              {achievement.is_unlocked ? <Icon className="w-5 h-5" /> : <Lock className="w-5 h-5" />}
            </div>
            
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between">
                <h4 className={`text-sm font-semibold ${
                  achievement.is_unlocked 
                    ? 'text-gray-900 dark:text-gray-100' 
                    : 'text-gray-500 dark:text-gray-400'
                }`}>
                  {achievement.name}
                </h4>
                {achievement.is_unlocked && (
                  <span className="text-xs text-yellow-600 dark:text-yellow-400 font-medium flex items-center gap-1">
                    <CheckCircle2 className="w-3 h-3" />
                    Unlocked
                  </span>
                )}
              </div>
              <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                {achievement.description}
              </p>
              {achievement.reward_tokens > 0 && (
                <div className="mt-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                  <Trophy className="w-3 h-3" />
                  +{achievement.reward_tokens} Tokens
                </div>
              )}
            </div>
          </div>
        )
      })}
    </div>
  )
}
