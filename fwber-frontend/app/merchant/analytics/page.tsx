'use client'

import { useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import MerchantHeader from '@/components/MerchantHeader'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useMerchantAnalytics, AnalyticsRange } from '@/lib/hooks/use-merchant-analytics'

export default function MerchantAnalyticsPage() {
  const [range, setRange] = useState<AnalyticsRange>('30d')
  const analytics = useMerchantAnalytics(range)

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <MerchantHeader title="Merchant Analytics" />
        <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
          <div className="mb-6 flex flex-wrap gap-2">
            {(['7d', '30d', '90d'] as AnalyticsRange[]).map((value) => (
              <Button key={value} variant={range === value ? 'default' : 'outline'} onClick={() => setRange(value)}>{value}</Button>
            ))}
          </div>

          {analytics.isLoading ? <div className="text-sm text-gray-500">Loading analytics…</div> : analytics.data && (
            <div className="space-y-6">
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {[
                  ['Gross revenue', `$${analytics.data.summary.gross_revenue.toFixed(2)}`],
                  ['Orders', analytics.data.summary.orders],
                  ['Issued redemptions', analytics.data.summary.issued_redemptions],
                  ['Redeemed redemptions', analytics.data.summary.redeemed_redemptions],
                  ['Redemption rate', `${analytics.data.summary.redemption_rate}%`],
                  ['Average order value', `$${analytics.data.summary.average_order_value.toFixed(2)}`],
                ].map(([label, value]) => (
                  <Card key={String(label)}>
                    <CardContent className="p-6">
                      <div className="text-sm text-gray-500">{label}</div>
                      <div className="mt-2 text-3xl font-black text-gray-900 dark:text-white">{value}</div>
                    </CardContent>
                  </Card>
                ))}
              </div>

              <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                  <CardHeader>
                    <CardTitle>Top items</CardTitle>
                    <CardDescription>Inventory with the strongest redemption pull.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {analytics.data.top_items.length ? analytics.data.top_items.map((item) => (
                      <div key={item.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="font-semibold text-gray-900 dark:text-white">{item.name}</div>
                        <div className="mt-1 text-sm text-gray-600 dark:text-gray-300">${Number(item.price_usd).toFixed(2)} · Stock {item.stock_count}</div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">Availability: {item.is_available ? 'Live' : 'Archived'}</div>
                      </div>
                    )) : <div className="text-sm text-gray-500">No item analytics yet.</div>}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Recent payments</CardTitle>
                    <CardDescription>Latest successful storefront charges and their status.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {analytics.data.recent_payments.length ? analytics.data.recent_payments.map((payment) => (
                      <div key={payment.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex items-center justify-between gap-3">
                          <div className="font-semibold text-gray-900 dark:text-white">{payment.description || 'Marketplace payment'}</div>
                          <div className="text-sm text-gray-600 dark:text-gray-300">{payment.status}</div>
                        </div>
                        <div className="mt-2 text-sm text-gray-600 dark:text-gray-300">${Number(payment.amount).toFixed(2)}</div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">{new Date(payment.created_at).toLocaleString()}</div>
                      </div>
                    )) : <div className="text-sm text-gray-500">No payment activity yet.</div>}
                  </CardContent>
                </Card>
              </div>
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}
