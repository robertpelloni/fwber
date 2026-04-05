'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { createEvent } from '@/lib/api/events'

export default function CreateEventPage() {
  const router = useRouter()
  const [form, setForm] = useState({
    title: '',
    description: '',
    location: '',
    latitude: '',
    longitude: '',
    start_time: '',
    end_time: '',
  })
  const [isSaving, setIsSaving] = useState(false)

  const update = (key: keyof typeof form, value: string) => setForm((current) => ({ ...current, [key]: value }))

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()
    setIsSaving(true)
    try {
      await createEvent({
        title: form.title,
        description: form.description,
        location_name: form.location,
        latitude: Number(form.latitude),
        longitude: Number(form.longitude),
        starts_at: new Date(form.start_time).toISOString(),
        ends_at: new Date(form.end_time).toISOString(),
      })
      router.push('/events')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Create Event" />
        <main className="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
          <form onSubmit={handleSubmit} className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-5">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Create Event</h1>
              <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">Restore a simple event creation flow so the `/events/create` route is real again.</p>
            </div>

            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
              <input name="title" value={form.title} onChange={(e) => update('title', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
            </div>

            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
              <textarea name="description" value={form.description} onChange={(e) => update('description', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" rows={5} />
            </div>

            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
              <input name="location" value={form.location} onChange={(e) => update('location', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                <input name="latitude" value={form.latitude} onChange={(e) => update('latitude', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
              </div>
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                <input name="longitude" value={form.longitude} onChange={(e) => update('longitude', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Start Time</label>
                <input type="datetime-local" name="start_time" value={form.start_time} onChange={(e) => update('start_time', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
              </div>
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">End Time</label>
                <input type="datetime-local" name="end_time" value={form.end_time} onChange={(e) => update('end_time', e.target.value)} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" />
              </div>
            </div>

            <button type="submit" disabled={isSaving} className="rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-60">
              {isSaving ? 'Creating…' : 'Create Event'}
            </button>
          </form>
        </main>
      </div>
    </ProtectedRoute>
  )
}
