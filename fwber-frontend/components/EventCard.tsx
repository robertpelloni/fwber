import React from 'react';
import Link from 'next/link';
import { Event } from '@/lib/hooks/use-events';
import { Calendar, MapPin, Users } from 'lucide-react';

interface EventCardProps {
  event: Event;
}

export function EventCard({ event }: EventCardProps) {
  return (
    <Link href={`/events/${event.id}`} className="block">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
        <h3 className="text-lg font-semibold mb-2">{event.title}</h3>
        <div className="flex items-center text-gray-600 dark:text-gray-300 mb-1">
          <Calendar className="w-4 h-4 mr-2" />
          <span className="text-sm">
            {new Date(event.starts_at).toLocaleDateString()} {new Date(event.starts_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
          </span>
        </div>
        <div className="flex items-center text-gray-600 dark:text-gray-300 mb-1">
          <MapPin className="w-4 h-4 mr-2" />
          <span className="text-sm truncate">{event.location_name}</span>
        </div>
        <div className="flex items-center text-gray-600 dark:text-gray-300">
          <Users className="w-4 h-4 mr-2" />
          <span className="text-sm">{event.attendees_count} attending</span>
        </div>
        {event.price && Number(event.price) > 0 && (
            <div className="mt-2 text-green-600 font-medium">
                ${Number(event.price).toFixed(2)}
            </div>
        )}
      </div>
    </Link>
  );
}
