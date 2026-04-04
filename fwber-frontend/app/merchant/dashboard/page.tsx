'use client'

import { useQuery } from '@tanstack/react-query'
import Link from 'next/link'
import { BarChart3, Package, Store, Ticket, DollarSign, ArrowRight } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import MerchantHeader from '@/components/MerchantHeader'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { getMerchantDashboard } from '@/lib/api/merchant'

export default function MerchantDashboardPage() {
  const dashboard = useQuery({
    queryKey: ['merchant-dashboard'],
    queryFn: getMerchantDashboard,
  })

  const data = dashboard.data

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <MerchantHeader title="Merchant Dashboard" />
        <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
          {dashboard.isLoading ? (
            <div className="text-sm text-gray-500">Loading merchant dashboard…</div>
          ) : !data ? (
            <Card>
              <CardContent className="p-8 text-center">
                <p className="text-gray-600 dark:text-gray-300">No merchant profile found yet.</p>
                <Link href="/merchant/register"><Button className="mt-4">Create merchant profile</Button></Link>
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-6">
              <Card className="overflow-hidden border-amber-200 dark:border-amber-900/40">
                <CardHeader className="bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500 text-white">
                  <CardTitle className="text-3xl font-black">{data.profile.business_name}</CardTitle>
                  <CardDescription className="text-amber-50">{data.profile.description || 'Your local storefront control center.'}</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-3 p-6">
                  <Link href="/merchant/inventory"><Button><Package className="mr-2 h-4 w-4" />Manage inventory</Button></Link>
                  <Link href="/merchant/profile"><Button variant="outline"><Store className="mr-2 h-4 w-4" />Edit profile</Button></Link>
                  <Link href={data.storefront_path}><Button variant="outline">View storefront <ArrowRight className="ml-2 h-4 w-4" /></Button></Link>
                </CardContent>
              </Card>

              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {[
                  { label: 'Inventory items', value: data.stats.inventory_count, icon: Package },
                  { label: 'Active listings', value: data.stats.active_items, icon: Store },
                  { label: 'Pending redemptions', value: data.stats.pending_redemptions, icon: Ticket },
                  { label: 'Gross revenue', value: `$${Number(data.stats.gross_revenue).toFixed(2)}`, icon: DollarSign },
                ].map((stat) => (
                  <Card key={stat.label}>
                    <CardContent className="flex items-center justify-between p-6">
                      <div>
                        <div className="text-sm text-gray-500">{stat.label}</div>
                        <div className="mt-2 text-3xl font-black text-gray-900 dark:text-white">{stat.value}</div>
                      </div>
                      <stat.icon className="h-8 w-8 text-amber-500" />
                    </CardContent>
                  </Card>
                ))}
              </div>

              <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                  <CardHeader>
                    <CardTitle>Recent inventory</CardTitle>
                    <CardDescription>Your latest items and stock state.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {data.recent_inventory.length ? data.recent_inventory.map((item) => (
                      <div key={item.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="flex items-center justify-between gap-3">
                          <div>
                            <div className="font-semibold text-gray-900 dark:text-white">{item.name}</div>
                            <div className="text-sm text-gray-500 dark:text-gray-400">${Number(item.price_usd).toFixed(2)} · Stock {item.stock_count}</div>
                          </div>
                          <div className={`rounded-full px-3 py-1 text-xs font-semibold ${item.is_available ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'}`}>
                            {item.is_available ? 'Available' : 'Archived'}
                          </div>
                        </div>
                      </div>
                    )) : <div className="text-sm text-gray-500">No inventory yet.</div>}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2"><BarChart3 className="h-5 w-5 text-blue-600" />Recent redemptions</CardTitle>
                    <CardDescription>The latest codes issued or redeemed from storefront purchases.</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {data.recent_redemptions.length ? data.recent_redemptions.map((redemption) => (
                      <div key={redemption.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div className="font-mono text-sm font-bold text-gray-900 dark:text-white">{redemption.redemption_code}</div>
                        <div className="mt-1 text-sm text-gray-600 dark:text-gray-300">{redemption.inventory?.name || 'Unknown item'} · {redemption.user?.name || 'Customer'}</div>
                        <div className="mt-1 text-xs text-gray-500 dark:text-gray-400">{redemption.redeemed_at ? `Redeemed ${new Date(redemption.redeemed_at).toLocaleString()}` : 'Awaiting redemption'}</div>
                      </div>
                    )) : <div className="text-sm text-gray-500">No redemptions yet.</div>}
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
