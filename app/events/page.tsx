'use client';

import React, { useState, useEffect } from 'react';
import { useNearbyEvents } from '@/lib/hooks/use-events';
import { EventCard } from '@/components/EventCard';
import Link from 'next/link';
import { Plus, MapPin } from 'lucide-react';

export default function EventsPage() {
  const [location, setLocation] = useState<{ latitude: number | null; longitude: number | null; error: string | null }>({
    latitude: null,
    longitude: null,
    error: null,
  });

  useEffect(() => {
    if (!navigator.geolocation) {
      setLocation(prev => ({ ...prev, error: 'Geolocation is not supported' }));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocation({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          error: null,
        });
      },
      (error) => {
        console.error('Error getting location:', error);
        setLocation(prev => ({ ...prev, error: 'Could not get location' }));
      }
    );
  }, []);

  const { data, isLoading } = useNearbyEvents(
    location.latitude || undefined,
    location.longitude || undefined
  );

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <div className="flex items-center space-x-2">
            <h1 className="text-2xl font-bold">Nearby Events</h1>
            {location.latitude && (
                <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full flex items-center">
                    <MapPin className="w-3 h-3 mr-1" />
                    Located
                </span>
            )}
        </div>
        <Link href="/events/create" className="bg-blue-600 text-white px-4 py-2 rounded-md flex items-center hover:bg-blue-700">
          <Plus className="w-4 h-4 mr-2" />
          Create Event
        </Link>
      </div>

      {location.error && (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">
            Location access denied or unavailable. Showing all events.
        </div>
      )}

      {isLoading ? (
        <div className="flex justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {data?.data?.map((event: any) => (
            <EventCard key={event.id} event={event} />
          ))}
          {data?.data?.length === 0 && (
            <div className="col-span-full text-center text-gray-500 py-8">
              No upcoming events found nearby.
            </div>
          )}
        </div>
      )}
    </div>
  );
}
