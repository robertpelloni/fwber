'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { Crown, Heart, Sparkles, ShieldCheck, ArrowRight } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { PremiumUpgradeModal } from '@/components/PremiumUpgradeModal'
import { usePremiumPlans, usePremiumStatus } from '@/lib/hooks/use-premium'

export default function PremiumPage() {
  const [isUpgradeOpen, setIsUpgradeOpen] = useState(false)
  const premiumStatus = usePremiumStatus()
  const premiumPlans = usePremiumPlans()

  const activePlan = useMemo(() => premiumPlans.data?.plans?.[0], [premiumPlans.data])

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Gold Premium" />
        <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <Card className="overflow-hidden border-yellow-200 dark:border-yellow-900/40">
              <CardHeader className="bg-gradient-to-br from-yellow-500 via-amber-500 to-orange-500 text-white">
                <div className="flex items-center gap-3">
                  <Crown className="h-8 w-8 fill-white" />
                  <div>
                    <CardTitle className="text-3xl font-black">Upgrade to Gold</CardTitle>
                    <CardDescription className="text-yellow-50">
                      Restore the premium dating layer without bringing back token-economy bloat.
                    </CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-6 p-6">
                <div className="grid gap-4 md:grid-cols-3">
                  {[
                    {
                      icon: Heart,
                      title: 'See who likes you',
                      description: 'Stop guessing and open the admirer list immediately.',
                    },
                    {
                      icon: Sparkles,
                      title: 'Unlimited swipes',
                      description: 'Remove session friction when you are actively discovering nearby people.',
                    },
                    {
                      icon: ShieldCheck,
                      title: 'Subscription visibility',
                      description: 'Track your premium state, expiry, and billing history in one place.',
                    },
                  ].map((feature) => (
                    <div key={feature.title} className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                      <feature.icon className="mb-3 h-6 w-6 text-yellow-600" />
                      <h3 className="font-semibold text-gray-900 dark:text-white">{feature.title}</h3>
                      <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{feature.description}</p>
                    </div>
                  ))}
                </div>

                <div className="flex flex-wrap gap-3">
                  {premiumStatus.data?.is_premium ? (
                    <Link href="/who-likes-you">
                      <Button className="bg-gradient-to-r from-pink-500 to-rose-600 text-white hover:from-pink-600 hover:to-rose-700">
                        Open who likes you
                        <ArrowRight className="ml-2 h-4 w-4" />
                      </Button>
                    </Link>
                  ) : (
                    <Button
                      className="bg-gradient-to-r from-yellow-500 to-yellow-700 text-white hover:from-yellow-600 hover:to-yellow-800"
                      onClick={() => setIsUpgradeOpen(true)}
                    >
                      Upgrade to Gold
                    </Button>
                  )}

                  <Link href="/settings/subscription">
                    <Button variant="outline">Billing & subscription settings</Button>
                  </Link>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Current status</CardTitle>
                <CardDescription>Your live premium state from the restored backend route surface.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {premiumStatus.isLoading ? (
                  <div className="text-sm text-gray-500">Loading premium status…</div>
                ) : premiumStatus.data?.is_premium ? (
                  <>
                    <div className="rounded-2xl border border-green-200 bg-green-50 p-4 dark:border-green-900/40 dark:bg-green-950/20">
                      <div className="text-sm font-semibold uppercase tracking-wide text-green-700 dark:text-green-300">Premium Active</div>
                      <div className="mt-2 text-2xl font-black text-gray-900 dark:text-white">Gold</div>
                      <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Expires {premiumStatus.data.expires_at ? new Date(premiumStatus.data.expires_at).toLocaleString() : 'unknown'}
                      </p>
                    </div>
                    <Link href="/who-likes-you" className="block">
                      <Button variant="outline" className="w-full">View who likes you</Button>
                    </Link>
                  </>
                ) : (
                  <>
                    <div className="rounded-2xl border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                      You are currently on the free tier.
                    </div>
                    <Button className="w-full" onClick={() => setIsUpgradeOpen(true)}>
                      Upgrade to Gold
                    </Button>
                  </>
                )}

                {activePlan && (
                  <div className="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/40 dark:bg-yellow-950/20">
                    <div className="text-sm font-semibold uppercase tracking-wide text-yellow-700 dark:text-yellow-300">Primary plan</div>
                    <div className="mt-1 text-lg font-bold text-gray-900 dark:text-white">{activePlan.display_name}</div>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{activePlan.description}</p>
                    <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                      ${activePlan.price_usd.toFixed(2)} / {activePlan.interval}
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </main>
        <PremiumUpgradeModal isOpen={isUpgradeOpen} onClose={() => setIsUpgradeOpen(false)} />
      </div>
    </ProtectedRoute>
  )
}
