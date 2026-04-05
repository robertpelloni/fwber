'use client'

import Link from 'next/link'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { EventCard } from '@/components/EventCard'
import EventInvitationsList from '@/components/events/EventInvitationsList'
import { useNearbyEvents } from '@/lib/hooks/use-events'
import { CalendarPlus } from 'lucide-react'

export default function EventsPage() {
  const { data, isLoading } = useNearbyEvents()
  const events = data?.data || []

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Events" />
        <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Events</h1>
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                  Restored the local events surface so notification/event links land on a real discovery layer again.
                </p>
              </div>
              <Link href="/events/create" className="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-3 text-sm font-semibold text-white hover:bg-purple-700">
                <CalendarPlus className="h-4 w-4" />
                Create Event
              </Link>
            </div>
          </div>

          <EventInvitationsList />

          {isLoading ? (
            <div className="rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900">Loading events…</div>
          ) : events.length === 0 ? (
            <div className="rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900">No upcoming events yet.</div>
          ) : (
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {events.map((event) => (
                <EventCard key={event.id} event={event} />
              ))}
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}
