'use client'

import { useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import EventPaymentModal from '@/components/events/EventPaymentModal'
import { useEvent, useRsvpEvent } from '@/lib/hooks/use-events'
import { useParams } from 'next/navigation'
import { Calendar, MapPin, Users } from 'lucide-react'

export default function EventDetailsPage() {
  const params = useParams<{ id: string }>()
  const eventId = String(params.id)
  const { data: event, isLoading } = useEvent(eventId)
  const { mutate: rsvpEvent, isPending } = useRsvpEvent()
  const [isPaymentOpen, setIsPaymentOpen] = useState(false)

  if (isLoading) {
    return <ProtectedRoute><div className="min-h-screen bg-gray-50 dark:bg-gray-950"><AppHeader title="Event" /><div className="p-8 text-sm text-gray-500">Loading event…</div></div></ProtectedRoute>
  }

  if (!event) {
    return <ProtectedRoute><div className="min-h-screen bg-gray-50 dark:bg-gray-950"><AppHeader title="Event" /><div className="p-8 text-sm text-gray-500">Event not found.</div></div></ProtectedRoute>
  }

  const handleAttend = () => {
    if (Number(event.price || 0) > 0 || Number(event.token_cost || 0) > 0) {
      setIsPaymentOpen(true)
      return
    }

    rsvpEvent({ id: event.id, status: 'attending' })
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title={event.title} />
        <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{event.title}</h1>
            <p className="mt-4 text-sm leading-7 text-gray-600 dark:text-gray-300">{event.description}</p>

            <div className="mt-6 grid gap-4 sm:grid-cols-3">
              <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div className="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white"><Calendar className="h-4 w-4 text-purple-500" /> When</div>
                <div className="text-sm text-gray-600 dark:text-gray-300">{new Date(event.starts_at).toLocaleString()}</div>
              </div>
              <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div className="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white"><MapPin className="h-4 w-4 text-blue-500" /> Where</div>
                <div className="text-sm text-gray-600 dark:text-gray-300">{event.location_name}</div>
              </div>
              <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div className="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white"><Users className="h-4 w-4 text-green-500" /> Attendance</div>
                <div className="text-sm text-gray-600 dark:text-gray-300">{event.attendees_count} attending</div>
              </div>
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
              <button onClick={handleAttend} disabled={isPending} className="rounded-xl bg-purple-600 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-60">
                {isPending ? 'Saving…' : 'Attend'}
              </button>
              <button onClick={() => rsvpEvent({ id: event.id, status: 'maybe' })} disabled={isPending} className="rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                Maybe
              </button>
            </div>
          </div>
        </main>
        <EventPaymentModal isOpen={isPaymentOpen} onClose={() => setIsPaymentOpen(false)} event={event} />
      </div>
    </ProtectedRoute>
  )
}
