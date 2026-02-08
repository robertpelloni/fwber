'use client'

import { useQuery } from '@tanstack/react-query'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Trophy, Lock, Star, Zap, Users, Shield, Award } from 'lucide-react'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { useAuth } from '@/lib/auth-context'
import { Skeleton } from '@/components/ui/skeleton'

type Achievement = {
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

const CATEGORY_ICONS: Record<string, any> = {
  'General': Star,
  'Social': Users,
  'Viral': Zap,
  'Safety': Shield,
  'Crypto': Award, // or specific icon
}

function AchievementCard({ achievement }: { achievement: Achievement }) {
  const Icon = CATEGORY_ICONS[achievement.category] || Trophy

  return (
    <Card className={`relative overflow-hidden transition-all duration-300 ${achievement.is_unlocked ? 'border-primary/50 bg-primary/5' : 'opacity-70 grayscale'}`}>
      <CardHeader className="flex flex-row items-center gap-4 pb-2">
        <div className={`p-3 rounded-full ${achievement.is_unlocked ? 'bg-primary/20 text-primary' : 'bg-gray-100 text-gray-400'}`}>
          {achievement.is_hidden ? <Lock className="w-6 h-6" /> : <Icon className="w-6 h-6" />}
        </div>
        <div className="flex-1">
          <CardTitle className="text-lg">
            {achievement.is_hidden ? 'Secret Achievement' : achievement.name}
          </CardTitle>
          <CardDescription>
            {achievement.is_hidden ? 'Keep playing to discover this achievement.' : achievement.description}
          </CardDescription>
        </div>
        {achievement.is_unlocked && (
          <Badge variant="default" className="bg-green-500 hover:bg-green-600">
            Unlocked
          </Badge>
        )}
      </CardHeader>
      <CardContent>
        <div className="flex justify-between items-center text-sm">
          <div className="flex items-center gap-1 text-amber-500 font-medium">
            <span className="text-xs uppercase tracking-wider text-muted-foreground mr-2">Reward:</span>
            <Zap className="w-4 h-4" />
            <span>{achievement.reward_tokens} Tokens</span>
          </div>
          {achievement.unlocked_at && (
            <span className="text-xs text-muted-foreground">
              {new Date(achievement.unlocked_at).toLocaleDateString()}
            </span>
          )}
        </div>
      </CardContent>

      {/* Shine effect for unlocked cards */}
      {achievement.is_unlocked && (
        <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br from-yellow-400/20 to-transparent rounded-full blur-xl pointer-events-none" />
      )}
    </Card>
  )
}

export default function AchievementsPage() {
  const { user } = useAuth()

  const { data: achievements, isLoading } = useQuery<Achievement[]>({
    queryKey: ['achievements'],
    queryFn: async () => {
      const response = await apiClient.get('/achievements')
      // The API returns { achievements: [...] } based on controller inspection
      // But let's handle if it returns array directly or wrapped
      return (response.data as any).achievements || response.data
    },
    enabled: !!user
  })

  // Group achievements by category
  const groupedAchievements = achievements?.reduce((acc, achievement) => {
    const category = achievement.category || 'General'
    if (!acc[category]) acc[category] = []
    acc[category].push(achievement)
    return acc
  }, {} as Record<string, Achievement[]>)

  const totalPoints = achievements?.reduce((sum, a) => sum + (a.is_unlocked ? a.reward_tokens : 0), 0) || 0
  const unlockedCount = achievements?.filter(a => a.is_unlocked).length || 0
  const totalCount = achievements?.length || 0

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 pb-12">
      <AppHeader />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Achievements</h1>
            <p className="text-gray-600 dark:text-gray-400 mt-1">
              Track your progress and earn rewards.
            </p>
          </div>

          {/* Stats Summary */}
          <div className="flex gap-4">
             <Card className="bg-white dark:bg-gray-800 shadow-sm">
                <CardContent className="p-4 flex items-center gap-3">
                    <div className="p-2 bg-amber-100 text-amber-600 rounded-lg">
                        <Zap className="w-5 h-5" />
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground font-medium">Earned</p>
                        <p className="text-xl font-bold">{totalPoints}</p>
                    </div>
                </CardContent>
             </Card>
             <Card className="bg-white dark:bg-gray-800 shadow-sm">
                <CardContent className="p-4 flex items-center gap-3">
                    <div className="p-2 bg-blue-100 text-blue-600 rounded-lg">
                        <Trophy className="w-5 h-5" />
                    </div>
                    <div>
                        <p className="text-xs text-muted-foreground font-medium">Unlocked</p>
                        <p className="text-xl font-bold">{unlockedCount}/{totalCount}</p>
                    </div>
                </CardContent>
             </Card>
          </div>
        </div>

        {isLoading ? (
           <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
             {[1, 2, 3, 4, 5, 6].map(i => (
               <Skeleton key={i} className="h-40 w-full rounded-xl" />
             ))}
           </div>
        ) : (
          <div className="space-y-10">
            {Object.entries(groupedAchievements || {}).map(([category, items]) => (
              <section key={category}>
                <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
                  {category}
                  <span className="text-sm font-normal text-muted-foreground bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                    {items.filter(i => i.is_unlocked).length}/{items.length}
                  </span>
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {items.map((achievement) => (
                    <AchievementCard key={achievement.id} achievement={achievement} />
                  ))}
                </div>
              </section>
            ))}

            {(!achievements || achievements.length === 0) && (
              <div className="text-center py-12">
                <Trophy className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">No achievements found</h3>
                <p className="text-gray-500">Check back later for new challenges!</p>
              </div>
            )}
          </div>
        )}
      </main>
    </div>
  )
}
