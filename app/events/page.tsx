'use client';

import React, { useState } from 'react';
import { useNearbyEvents } from '@/lib/hooks/use-events';
import { EventCard } from '@/components/EventCard';
import Link from 'next/link';
import { Plus } from 'lucide-react';

export default function EventsPage() {
  // In a real app, we'd get user's location. For now, we can default or ask.
  // Assuming the hook handles undefined lat/lon by returning all or default.
  const { data, isLoading } = useNearbyEvents();

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Nearby Events</h1>
        <Link href="/events/create" className="bg-blue-600 text-white px-4 py-2 rounded-md flex items-center hover:bg-blue-700">
          <Plus className="w-4 h-4 mr-2" />
          Create Event
        </Link>
      </div>

      {isLoading ? (
        <div>Loading events...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {data?.data?.map((event: any) => (
            <EventCard key={event.id} event={event} />
          ))}
          {data?.data?.length === 0 && (
            <div className="col-span-full text-center text-gray-500">
              No upcoming events found nearby.
            </div>
          )}
        </div>
      )}
    </div>
  );
}
