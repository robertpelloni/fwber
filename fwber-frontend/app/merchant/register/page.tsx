'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Store, Briefcase, MapPin, FileText, LocateFixed } from 'lucide-react'
import ProtectedRoute from '@/components/ProtectedRoute'
import MerchantHeader from '@/components/MerchantHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useToast } from '@/components/ToastProvider'
import { registerMerchant } from '@/lib/api/merchant'
import { useAuth } from '@/lib/auth-context'

export default function MerchantRegisterPage() {
  const router = useRouter()
  const { user, updateUser } = useAuth()
  const { showError, showSuccess, showInfo } = useToast()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [isLocating, setIsLocating] = useState(false)
  const [form, setForm] = useState({
    business_name: user?.name ? `${user.name}'s Shop` : '',
    category: 'nightlife',
    description: '',
    address: '',
    location_name: '',
    latitude: '',
    longitude: '',
  })

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

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()
    setIsSubmitting(true)

    try {
      const response = await registerMerchant({
        ...form,
        latitude: form.latitude ? Number(form.latitude) : null,
        longitude: form.longitude ? Number(form.longitude) : null,
      })
      if (response.user) {
        updateUser(response.user)
      }
      showSuccess('Merchant profile created', 'Your storefront tools are now active.')
      router.push('/merchant/dashboard')
    } catch (error) {
      showError('Merchant setup failed', error instanceof Error ? error.message : 'Unable to create merchant profile.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <MerchantHeader title="Become a Merchant" showNav={false} />
        <main className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
          <Card>
            <CardHeader>
              <CardTitle className="text-3xl font-black">Launch your merchant profile</CardTitle>
              <CardDescription>
                Restore the local commerce layer: create a merchant identity, publish inventory, and start issuing redemption codes for nearby buyers.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="business_name" className="flex items-center gap-2"><Store className="h-4 w-4" />Business name</Label>
                    <Input id="business_name" name="business_name" value={form.business_name} onChange={(e) => setForm((prev) => ({ ...prev, business_name: e.target.value }))} required />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="category" className="flex items-center gap-2"><Briefcase className="h-4 w-4" />Category</Label>
                    <select id="category" name="category" value={form.category} onChange={(e) => setForm((prev) => ({ ...prev, category: e.target.value }))} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                      <option value="nightlife">Nightlife</option>
                      <option value="food">Food</option>
                      <option value="retail">Retail</option>
                      <option value="wellness">Wellness</option>
                      <option value="events">Events</option>
                    </select>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="description" className="flex items-center gap-2"><FileText className="h-4 w-4" />Description</Label>
                  <Textarea id="description" name="description" value={form.description} onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))} placeholder="Describe what you sell and why people nearby should care." rows={5} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="address" className="flex items-center gap-2"><MapPin className="h-4 w-4" />Address</Label>
                  <Input id="address" name="address" value={form.address} onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))} placeholder="123 Main St" />
                </div>
                <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                  <div className="mb-4 flex items-center justify-between gap-4">
                    <div>
                      <h3 className="font-semibold text-gray-900 dark:text-white">Nearby discovery location</h3>
                      <p className="text-sm text-gray-600 dark:text-gray-300">This controls how your storefront appears in nearby marketplace and AR results.</p>
                    </div>
                    <Button type="button" variant="outline" onClick={useCurrentLocation} disabled={isLocating}>
                      <LocateFixed className="mr-2 h-4 w-4" />
                      {isLocating ? 'Locating…' : 'Use current location'}
                    </Button>
                  </div>
                  <div className="grid gap-4 md:grid-cols-3">
                    <div className="space-y-2 md:col-span-3">
                      <Label htmlFor="location_name">Location label</Label>
                      <Input id="location_name" value={form.location_name} onChange={(e) => setForm((prev) => ({ ...prev, location_name: e.target.value }))} placeholder="Downtown Detroit" />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="latitude">Latitude</Label>
                      <Input id="latitude" value={form.latitude} onChange={(e) => setForm((prev) => ({ ...prev, latitude: e.target.value }))} placeholder="42.3314" />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="longitude">Longitude</Label>
                      <Input id="longitude" value={form.longitude} onChange={(e) => setForm((prev) => ({ ...prev, longitude: e.target.value }))} placeholder="-83.0458" />
                    </div>
                    <div className="space-y-2">
                      <Label>Why it matters</Label>
                      <div className="rounded-md border border-dashed border-amber-300 px-3 py-2 text-sm text-gray-600 dark:border-amber-800 dark:text-gray-300">
                        Nearby ranking uses this point to calculate real merchant distance.
                      </div>
                    </div>
                  </div>
                </div>
                <Button type="submit" disabled={isSubmitting} className="w-full bg-gradient-to-r from-amber-500 to-orange-600 text-white hover:from-amber-600 hover:to-orange-700">
                  {isSubmitting ? 'Creating merchant profile…' : 'Create merchant profile'}
                </Button>
              </form>
            </CardContent>
          </Card>
        </main>
      </div>
    </ProtectedRoute>
  )
}
