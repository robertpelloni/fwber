'use client'

import { useEffect, useState } from 'react'
import { LocateFixed, MapPin } from 'lucide-react'
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
  const { showError, showSuccess, showInfo } = useToast()
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [isLocating, setIsLocating] = useState(false)
  const [trust, setTrust] = useState<{ trust_score: number; trust_tier: string } | null>(null)
  const [form, setForm] = useState({
    business_name: '',
    category: '',
    description: '',
    address: '',
    location_name: '',
    latitude: '',
    longitude: '',
  })

  useEffect(() => {
    getMerchantProfile()
      .then((result) => {
        const profile = result.profile
        setForm({
          business_name: profile.business_name,
          category: profile.category,
          description: profile.description || '',
          address: profile.address || '',
          location_name: profile.location_name || '',
          latitude: profile.latitude != null ? String(profile.latitude) : '',
          longitude: profile.longitude != null ? String(profile.longitude) : '',
        })
        setTrust(result.trust)
      })
      .catch((error) => showError('Failed to load merchant profile', error instanceof Error ? error.message : 'Unable to load merchant profile.'))
      .finally(() => setIsLoading(false))
  }, [showError])

  const useCurrentLocation = async () => {
    if (!navigator.geolocation) {
      showError('Location unavailable', 'This browser does not support geolocation for merchant discovery setup.')
      return
    }

    setIsLocating(true)

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setForm((prev) => ({
          ...prev,
          latitude: position.coords.latitude.toFixed(6),
          longitude: position.coords.longitude.toFixed(6),
        }))
        showInfo('Location captured', 'Latitude and longitude were filled from your device location.')
        setIsLocating(false)
      },
      (error) => {
        showError('Location failed', error.message || 'Unable to read your current location.')
        setIsLocating(false)
      },
      { enableHighAccuracy: true, timeout: 10000 }
    )
  }

  const save = async () => {
    setIsSaving(true)
    try {
      const result = await updateMerchantProfile({
        ...form,
        latitude: form.latitude ? Number(form.latitude) : null,
        longitude: form.longitude ? Number(form.longitude) : null,
      })
      setTrust(result.trust)
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
              <CardDescription>Update the merchant identity, public copy, and real-world location used for nearby marketplace ranking.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {isLoading ? <div className="text-sm text-gray-500">Loading merchant profile…</div> : (
                <>
                  <div className="space-y-2"><Label>Business name</Label><Input value={form.business_name} onChange={(e) => setForm((prev) => ({ ...prev, business_name: e.target.value }))} /></div>
                  <div className="space-y-2"><Label>Category</Label><Input value={form.category} onChange={(e) => setForm((prev) => ({ ...prev, category: e.target.value }))} /></div>
                  <div className="space-y-2"><Label>Description</Label><Textarea value={form.description} onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))} rows={5} /></div>
                  <div className="space-y-2"><Label>Address</Label><Input value={form.address} onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))} /></div>

                  {trust && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
                      <div className="font-semibold text-gray-900 dark:text-white">Merchant trust score: {trust.trust_score}</div>
                      <div className="text-gray-600 dark:text-gray-300">Current tier: {trust.trust_tier}</div>
                    </div>
                  )}

                  <div className="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                    <div className="mb-4 flex items-center justify-between gap-4">
                      <div>
                        <h3 className="font-semibold text-gray-900 dark:text-white">Geo-aware storefront location</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-300">Nearby marketplace and AR inventory overlays now rank using this saved location.</p>
                      </div>
                      <Button type="button" variant="outline" onClick={useCurrentLocation} disabled={isLocating}>
                        <LocateFixed className="mr-2 h-4 w-4" />
                        {isLocating ? 'Locating…' : 'Use current location'}
                      </Button>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                      <div className="space-y-2 md:col-span-2">
                        <Label className="flex items-center gap-2"><MapPin className="h-4 w-4" />Location label</Label>
                        <Input value={form.location_name} onChange={(e) => setForm((prev) => ({ ...prev, location_name: e.target.value }))} placeholder="Downtown Detroit" />
                      </div>
                      <div className="space-y-2"><Label>Latitude</Label><Input value={form.latitude} onChange={(e) => setForm((prev) => ({ ...prev, latitude: e.target.value }))} placeholder="42.3314" /></div>
                      <div className="space-y-2"><Label>Longitude</Label><Input value={form.longitude} onChange={(e) => setForm((prev) => ({ ...prev, longitude: e.target.value }))} placeholder="-83.0458" /></div>
                    </div>
                  </div>

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
