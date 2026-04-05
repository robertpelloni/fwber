'use client'

import { useEffect, useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { apiClient } from '@/lib/api/client'
import { useAuth } from '@/lib/auth-context'
import { Plane, MapPin, Save } from 'lucide-react'

export default function TravelModePage() {
  const { user } = useAuth()
  const [enabled, setEnabled] = useState(false)
  const [locationName, setLocationName] = useState('')
  const [latitude, setLatitude] = useState('')
  const [longitude, setLongitude] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    const profile = user?.profile as Record<string, unknown> | undefined
    setEnabled(Boolean(profile?.is_travel_mode))
    setLocationName(typeof profile?.travel_location_name === 'string' ? profile.travel_location_name : '')
    setLatitude(profile?.travel_latitude != null ? String(profile.travel_latitude) : '')
    setLongitude(profile?.travel_longitude != null ? String(profile.travel_longitude) : '')
  }, [user])

  const handleSave = async () => {
    setIsSaving(true)
    setMessage(null)

    try {
      await apiClient.put('/profile', {
        is_travel_mode: enabled,
        travel_location: enabled
          ? {
              name: locationName || null,
              latitude: latitude ? Number(latitude) : null,
              longitude: longitude ? Number(longitude) : null,
            }
          : null,
      })

      setMessage('Travel mode updated successfully.')
    } catch (error) {
      console.error('Failed to save travel mode', error)
      setMessage('Failed to save travel mode. Please verify the coordinates and try again.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Travel Mode" />
        <main className="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div className="mb-6 flex items-start gap-3">
              <div className="rounded-2xl bg-blue-100 p-3 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                <Plane className="h-6 w-6" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Travel Mode</h1>
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                  Restored the missing travel-mode page so the Settings entry now leads to a real control surface instead of a dead route.
                </p>
              </div>
            </div>

            <div className="space-y-5">
              <label className="flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-4 dark:border-gray-800">
                <input
                  type="checkbox"
                  checked={enabled}
                  onChange={(event) => setEnabled(event.target.checked)}
                  className="h-4 w-4"
                />
                <div>
                  <div className="font-medium text-gray-900 dark:text-white">Enable travel mode</div>
                  <div className="text-sm text-gray-500 dark:text-gray-400">Appear in another city without overwriting your base home location.</div>
                </div>
              </label>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Travel location name</label>
                <div className="relative">
                  <MapPin className="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-gray-400" />
                  <input
                    value={locationName}
                    onChange={(event) => setLocationName(event.target.value)}
                    placeholder="Las Vegas, NV"
                    className="w-full rounded-xl border border-gray-300 pl-10 pr-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                  />
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                  <input
                    value={latitude}
                    onChange={(event) => setLatitude(event.target.value)}
                    placeholder="36.1699"
                    className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                  />
                </div>
                <div>
                  <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                  <input
                    value={longitude}
                    onChange={(event) => setLongitude(event.target.value)}
                    placeholder="-115.1398"
                    className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                  />
                </div>
              </div>

              {message ? (
                <div className="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950/60 dark:text-gray-300">
                  {message}
                </div>
              ) : null}

              <button
                onClick={handleSave}
                disabled={isSaving}
                className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
              >
                <Save className="h-4 w-4" />
                {isSaving ? 'Saving…' : 'Save travel mode'}
              </button>
            </div>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  )
}
