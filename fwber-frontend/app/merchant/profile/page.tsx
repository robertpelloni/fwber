'use client'

import { useEffect, useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import MerchantHeader from '@/components/MerchantHeader'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { getMerchantProfile, updateMerchantProfile } from '@/lib/api/merchant'
import { useToast } from '@/components/ToastProvider'

export default function MerchantProfilePage() {
  const { showError, showSuccess } = useToast()
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [form, setForm] = useState({ business_name: '', category: '', description: '', address: '' })

  useEffect(() => {
    getMerchantProfile()
      .then((result) => {
        const profile = result.profile
        setForm({
          business_name: profile.business_name,
          category: profile.category,
          description: profile.description || '',
          address: profile.address || '',
        })
      })
      .catch((error) => showError('Failed to load merchant profile', error instanceof Error ? error.message : 'Unable to load merchant profile.'))
      .finally(() => setIsLoading(false))
  }, [showError])

  const save = async () => {
    setIsSaving(true)
    try {
      await updateMerchantProfile(form)
      showSuccess('Merchant profile updated', 'Your storefront details were saved.')
    } catch (error) {
      showError('Save failed', error instanceof Error ? error.message : 'Unable to save merchant profile.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <MerchantHeader title="Merchant Profile" />
        <main className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
          <Card>
            <CardHeader>
              <CardTitle>Storefront details</CardTitle>
              <CardDescription>Update the merchant identity and public copy shown to buyers.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {isLoading ? <div className="text-sm text-gray-500">Loading merchant profile…</div> : (
                <>
                  <div className="space-y-2"><Label>Business name</Label><Input value={form.business_name} onChange={(e) => setForm((prev) => ({ ...prev, business_name: e.target.value }))} /></div>
                  <div className="space-y-2"><Label>Category</Label><Input value={form.category} onChange={(e) => setForm((prev) => ({ ...prev, category: e.target.value }))} /></div>
                  <div className="space-y-2"><Label>Description</Label><Textarea value={form.description} onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))} rows={5} /></div>
                  <div className="space-y-2"><Label>Address</Label><Input value={form.address} onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))} /></div>
                  <Button onClick={save} disabled={isSaving} className="w-full">{isSaving ? 'Saving…' : 'Save merchant profile'}</Button>
                </>
              )}
            </CardContent>
          </Card>
        </main>
      </div>
    </ProtectedRoute>
  )
}
