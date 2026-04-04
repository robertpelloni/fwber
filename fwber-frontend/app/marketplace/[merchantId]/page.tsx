'use client'

import { useState } from 'react'
import { useParams } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { Package, Store, MapPin, ReceiptText, Navigation } from 'lucide-react'
import AppHeader from '@/components/AppHeader'
import ProtectedRoute from '@/components/ProtectedRoute'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { marketplaceApi } from '@/lib/api/marketplace'
import { useToast } from '@/components/ToastProvider'
import { DigitalReceipt } from '@/components/marketplace/DigitalReceipt'

export default function MarketplaceMerchantPage() {
  const params = useParams<{ merchantId: string }>()
  const { showError, showSuccess } = useToast()
  const [receipt, setReceipt] = useState<null | { id: number | string; itemName: string; price: string | number; merchantName: string; timestamp: string; redemptionCode?: string }>(null)

  const merchant = useQuery({
    queryKey: ['marketplace-merchant', params.merchantId],
    queryFn: () => marketplaceApi.getInventory(params.merchantId),
    enabled: !!params.merchantId,
  })

  const purchase = async (itemId: number, itemName: string, price: string | number) => {
    try {
      const result = await marketplaceApi.purchaseItem(itemId)
      showSuccess('Purchase completed', result.message)
      setReceipt({
        id: result.receipt?.id || result.redemption_code,
        itemName,
        price,
        merchantName: result.merchant_name || merchant.data?.merchant.business_name || 'Merchant',
        timestamp: result.receipt?.paid_at || new Date().toISOString(),
        redemptionCode: result.redemption_code,
      })
      merchant.refetch()
    } catch (error) {
      showError('Purchase failed', error instanceof Error ? error.message : 'Unable to complete marketplace purchase.')
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Marketplace" />
        <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
          {merchant.isLoading ? <div className="text-sm text-gray-500">Loading storefront…</div> : merchant.data && (
            <div className="space-y-6">
              <Card className="overflow-hidden border-emerald-200 dark:border-emerald-900/40">
                <CardHeader className="bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 text-white">
                  <CardTitle className="flex items-center gap-3 text-3xl font-black"><Store className="h-7 w-7" />{merchant.data.merchant.business_name}</CardTitle>
                  <CardDescription className="text-emerald-50">{merchant.data.merchant.description || 'Browse locally redeemable items from this merchant storefront.'}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3 p-6">
                  <div className="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300">
                    <div className="inline-flex items-center gap-2"><Package className="h-4 w-4 text-emerald-600" />{merchant.data.items.length} items available</div>
                    {(merchant.data.merchant.location_name || merchant.data.merchant.address) && <div className="inline-flex items-center gap-2"><MapPin className="h-4 w-4 text-emerald-600" />{merchant.data.merchant.location_name || merchant.data.merchant.address}</div>}
                    {merchant.data.merchant.latitude != null && merchant.data.merchant.longitude != null && <div className="inline-flex items-center gap-2"><Navigation className="h-4 w-4 text-emerald-600" />{merchant.data.merchant.latitude.toFixed(4)}, {merchant.data.merchant.longitude.toFixed(4)}</div>}
                  </div>
                </CardContent>
              </Card>

              <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                {merchant.data.items.map((item) => (
                  <Card key={item.id}>
                    <CardHeader>
                      <CardTitle>{item.name}</CardTitle>
                      <CardDescription>{item.description || 'Local redemption item.'}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="text-3xl font-black text-gray-900 dark:text-white">${Number(item.price_usd).toFixed(2)}</div>
                      <div className="text-sm text-gray-500 dark:text-gray-400">Stock remaining: {item.stock_count}</div>
                      <Button className="w-full" onClick={() => purchase(item.id, item.name, item.price_usd)} disabled={item.stock_count < 1}>
                        <ReceiptText className="mr-2 h-4 w-4" />
                        {item.stock_count < 1 ? 'Sold out' : 'Buy now'}
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          )}
        </main>
        {receipt && <DigitalReceipt {...receipt} onClose={() => setReceipt(null)} />}
      </div>
    </ProtectedRoute>
  )
}
