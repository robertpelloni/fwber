'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { useAuth } from '@/lib/auth-context'
import { getMerchantProfile, getPromotions, MerchantProfile, Promotion } from '@/lib/api/merchant'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { PlusCircle, Tag } from 'lucide-react'

export default function MerchantDashboard() {
  const { token } = useAuth()
  const [profile, setProfile] = useState<MerchantProfile | null>(null)
  const [promotions, setPromotions] = useState<Promotion[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function loadData() {
      if (!token) return
      try {
        const [profileData, promotionsData] = await Promise.all([
            getMerchantProfile(token),
            getPromotions(token)
        ])
        setProfile(profileData)
        setPromotions(promotionsData)
      } catch (error) {
        console.error(error)
      } finally {
        setLoading(false)
      }
    }
    loadData()
  }, [token])

  if (loading) {
     return <DashboardSkeleton />
  }

  return (
    <div className="space-y-6 px-4 sm:px-0">
        {/* Welcome Section */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Dashboard</h1>
                <p className="text-gray-500 dark:text-gray-400">
                    Welcome back, {profile?.business_name}
                </p>
            </div>
            <Link href="/merchant/promotions/new">
                <Button className="bg-amber-500 hover:bg-amber-600 text-white">
                    <PlusCircle className="mr-2 h-4 w-4" />
                    Create Promotion
                </Button>
            </Link>
        </div>

        {/* Stats Grid */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Active Promotions</CardTitle>
                    <Tag className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {promotions.filter(p => p.is_active).length}
                    </div>
                </CardContent>
            </Card>
             <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Total Promotions</CardTitle>
                    <Tag className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{promotions.length}</div>
                </CardContent>
            </Card>
        </div>
        
        {/* Recent Promotions */}
        <div>
            <div className="flex items-center justify-between mb-4">
                 <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Recent Promotions</h2>
                 <Link href="/merchant/promotions" className="text-sm text-amber-600 hover:underline">
                    View all
                 </Link>
            </div>
             {promotions.length === 0 ? (
                <Card className="bg-gray-50 dark:bg-gray-800/50 border-dashed">
                    <CardContent className="flex flex-col items-center justify-center py-10 text-center">
                        <Tag className="h-10 w-10 text-gray-400 mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">No promotions yet</h3>
                        <p className="text-gray-500 dark:text-gray-400 mb-4 max-w-sm">
                            Create your first promotion to start attracting customers to your business.
                        </p>
                         <Link href="/merchant/promotions/new">
                            <Button variant="outline">Create Promotion</Button>
                        </Link>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {promotions.slice(0, 3).map((promo) => (
                         <Card key={promo.id}>
                            <CardHeader>
                                <CardTitle className="text-lg">{promo.title}</CardTitle>
                                <CardDescription className="line-clamp-2">{promo.description}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex justify-between items-center text-sm">
                                    <span className="font-bold text-green-600 dark:text-green-400">{promo.discount_value}</span>
                                    <span className={`px-2 py-1 rounded-full text-xs ${promo.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'}`}>
                                        {promo.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    </div>
  )
}

function DashboardSkeleton() {
    return (
        <div className="space-y-6">
            <div className="h-16 w-1/3 bg-gray-200 dark:bg-gray-800 rounded animate-pulse" />
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {[1,2,3,4].map(i => <Skeleton key={i} className="h-24" />)}
            </div>
             <div className="space-y-4">
                <Skeleton className="h-8 w-48" />
                 <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                     {[1,2,3].map(i => <Skeleton key={i} className="h-40" />)}
                 </div>
            </div>
        </div>
    )
}
