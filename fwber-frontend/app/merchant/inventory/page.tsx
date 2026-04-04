'use client'

import { useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Archive, Package, Plus, Ticket } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import MerchantHeader from '@/components/MerchantHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { marketplaceApi } from '@/lib/api/marketplace'
import { useToast } from '@/components/ToastProvider'

export default function MerchantInventoryPage() {
  const queryClient = useQueryClient()
  const { showError, showSuccess } = useToast()
  const [redeemCode, setRedeemCode] = useState('')
  const [form, setForm] = useState({ name: '', description: '', price_usd: '15', stock_count: '10', image_url: '' })
  const inventory = useQuery({ queryKey: ['merchant-inventory'], queryFn: marketplaceApi.getOwnedInventory })

  const createItem = async () => {
    try {
      await marketplaceApi.createItem({
        name: form.name,
        description: form.description,
        price_usd: Number(form.price_usd),
        stock_count: Number(form.stock_count),
        image_url: form.image_url || undefined,
        is_available: true,
      })
      showSuccess('Inventory item created', 'Your storefront listing is now live.')
      setForm({ name: '', description: '', price_usd: '15', stock_count: '10', image_url: '' })
      queryClient.invalidateQueries({ queryKey: ['merchant-inventory'] })
      queryClient.invalidateQueries({ queryKey: ['merchant-dashboard'] })
    } catch (error) {
      showError('Failed to create item', error instanceof Error ? error.message : 'Unable to create inventory item.')
    }
  }

  const archiveItem = async (id: number) => {
    try {
      await marketplaceApi.archiveItem(id)
      showSuccess('Inventory archived', 'The item was hidden from the storefront.')
      queryClient.invalidateQueries({ queryKey: ['merchant-inventory'] })
      queryClient.invalidateQueries({ queryKey: ['merchant-dashboard'] })
    } catch (error) {
      showError('Archive failed', error instanceof Error ? error.message : 'Unable to archive item.')
    }
  }

  const redeem = async () => {
    try {
      const result = await marketplaceApi.redeemCode(redeemCode)
      showSuccess('Code redeemed', `${result.user_name} picked up ${result.item_name}.`)
      setRedeemCode('')
      queryClient.invalidateQueries({ queryKey: ['merchant-dashboard'] })
    } catch (error) {
      showError('Redeem failed', error instanceof Error ? error.message : 'Unable to redeem code.')
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <MerchantHeader title="Inventory" />
        <main className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
          <div className="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2"><Plus className="h-5 w-5 text-emerald-600" />Add inventory item</CardTitle>
                <CardDescription>Create a storefront listing with price, stock, and pickup/redemption support.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2"><Label>Name</Label><Input value={form.name} onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))} /></div>
                <div className="space-y-2"><Label>Description</Label><Textarea value={form.description} onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))} rows={4} /></div>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2"><Label>Price (USD)</Label><Input type="number" min="0" step="0.01" value={form.price_usd} onChange={(e) => setForm((prev) => ({ ...prev, price_usd: e.target.value }))} /></div>
                  <div className="space-y-2"><Label>Stock count</Label><Input type="number" min="0" step="1" value={form.stock_count} onChange={(e) => setForm((prev) => ({ ...prev, stock_count: e.target.value }))} /></div>
                </div>
                <div className="space-y-2"><Label>Image URL</Label><Input value={form.image_url} onChange={(e) => setForm((prev) => ({ ...prev, image_url: e.target.value }))} placeholder="https://..." /></div>
                <Button onClick={createItem} className="w-full">Create item</Button>
              </CardContent>
            </Card>

            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2"><Ticket className="h-5 w-5 text-amber-600" />Redeem pickup code</CardTitle>
                  <CardDescription>Mark a customer purchase as collected or consumed.</CardDescription>
                </CardHeader>
                <CardContent className="flex gap-3">
                  <Input value={redeemCode} onChange={(e) => setRedeemCode(e.target.value)} placeholder="FWB-ABCDEFGH" />
                  <Button onClick={redeem}>Redeem</Button>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2"><Package className="h-5 w-5 text-blue-600" />Current inventory</CardTitle>
                  <CardDescription>Active and archived storefront items.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                  {inventory.isLoading ? <div className="text-sm text-gray-500">Loading inventory…</div> : inventory.data?.items?.length ? inventory.data.items.map((item) => (
                    <div key={item.id} className="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                          <div className="font-semibold text-gray-900 dark:text-white">{item.name}</div>
                          <div className="mt-1 text-sm text-gray-600 dark:text-gray-300">${Number(item.price_usd).toFixed(2)} · Stock {item.stock_count}</div>
                          {item.description && <div className="mt-2 text-sm text-gray-500 dark:text-gray-400">{item.description}</div>}
                        </div>
                        <div className="flex items-center gap-2">
                          <span className={`rounded-full px-3 py-1 text-xs font-semibold ${item.is_available ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'}`}>{item.is_available ? 'Available' : 'Archived'}</span>
                          {item.is_available && <Button variant="outline" size="sm" onClick={() => archiveItem(item.id)}><Archive className="mr-2 h-4 w-4" />Archive</Button>}
                        </div>
                      </div>
                    </div>
                  )) : <div className="text-sm text-gray-500">No inventory items yet.</div>}
                </CardContent>
              </Card>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
