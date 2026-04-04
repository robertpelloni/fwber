'use client'

import Link from 'next/link'
import { CreditCard, Crown, ReceiptText } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { usePremiumHistory, usePremiumStatus } from '@/lib/hooks/use-premium'

export default function SubscriptionSettingsPage() {
  const premiumStatus = usePremiumStatus()
  const premiumHistory = usePremiumHistory()

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Subscription & Billing" />
        <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2"><Crown className="h-5 w-5 text-yellow-600" /> Current plan</CardTitle>
                <CardDescription>Live premium entitlement state from the restored billing endpoints.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {premiumStatus.isLoading ? (
                  <div className="text-sm text-gray-500">Loading subscription status…</div>
                ) : premiumStatus.data?.is_premium ? (
                  <div className="rounded-2xl border border-green-200 bg-green-50 p-4 dark:border-green-900/40 dark:bg-green-950/20">
                    <div className="text-sm font-semibold uppercase tracking-wide text-green-700 dark:text-green-300">Gold active</div>
                    <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                      Expires {premiumStatus.data.expires_at ? new Date(premiumStatus.data.expires_at).toLocaleString() : 'unknown'}
                    </p>
                    {premiumStatus.data.active_plan && (
                      <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                        Subscription status: {premiumStatus.data.active_plan.status}
                      </p>
                    )}
                  </div>
                ) : (
                  <div className="rounded-2xl border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                    You are currently on the free tier.
                  </div>
                )}

                <Link href="/premium" className="block">
                  <Button className="w-full bg-gradient-to-r from-yellow-500 to-yellow-700 text-white hover:from-yellow-600 hover:to-yellow-800">
                    <CreditCard className="mr-2 h-4 w-4" />
                    {premiumStatus.data?.is_premium ? 'Manage upgrade options' : 'Upgrade to Gold'}
                  </Button>
                </Link>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2"><ReceiptText className="h-5 w-5 text-blue-600" /> Billing history</CardTitle>
                <CardDescription>Compact payment and subscription history restored for transparency and support debugging.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div>
                  <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Payments</h3>
                  <div className="space-y-3">
                    {premiumHistory.data?.payments?.length ? premiumHistory.data.payments.map((payment) => (
                      <div key={`payment-${payment.id}`} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <div className="font-semibold text-gray-900 dark:text-white">{payment.description || 'Premium payment'}</div>
                          <div className="text-sm font-medium text-gray-600 dark:text-gray-300">{payment.status}</div>
                        </div>
                        <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                          {payment.amount ? `$${payment.amount} ${payment.currency || ''}` : 'Amount unavailable'} · {payment.payment_gateway || 'gateway unknown'}
                        </div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">{new Date(payment.created_at).toLocaleString()}</div>
                      </div>
                    )) : (
                      <div className="rounded-2xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No payment records yet.
                      </div>
                    )}
                  </div>
                </div>

                <div>
                  <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Subscriptions</h3>
                  <div className="space-y-3">
                    {premiumHistory.data?.subscriptions?.length ? premiumHistory.data.subscriptions.map((subscription) => (
                      <div key={`subscription-${subscription.id}`} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <div className="font-semibold text-gray-900 dark:text-white">{subscription.name || 'Gold'}</div>
                          <div className="text-sm font-medium text-gray-600 dark:text-gray-300">{subscription.status}</div>
                        </div>
                        <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                          Ends {subscription.ends_at ? new Date(subscription.ends_at).toLocaleString() : 'not scheduled'}
                        </div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">Created {new Date(subscription.created_at).toLocaleString()}</div>
                      </div>
                    )) : (
                      <div className="rounded-2xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No subscription records yet.
                      </div>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
