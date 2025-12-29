'use client';

import React, { useState } from 'react';
import { useEvent, useRsvpEvent } from '@/lib/hooks/use-events';
import { useParams } from 'next/navigation';
import { Calendar, MapPin, Users, DollarSign, UserPlus, Coins } from 'lucide-react';
import InviteUserModal from '@/components/events/InviteUserModal';
import EventPaymentModal from '@/components/events/EventPaymentModal';

export default function EventDetailPage() {
  const { id } = useParams();
  const { data: event, isLoading } = useEvent(id as string);
  const rsvp = useRsvpEvent();
  const [isInviteModalOpen, setIsInviteModalOpen] = useState(false);
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);

  if (isLoading) return <div className="p-8">Loading...</div>;
  if (!event) return <div className="p-8">Event not found</div>;

  const handleRsvp = (status: string) => {
    // If declining or just 'maybe', proceed directly
    if (status !== 'attending') {
       rsvp.mutate({ id: event.id, status });
       return;
    }

    const hasPrice = (event.price && Number(event.price) > 0) || (event.token_cost && Number(event.token_cost) > 0);
    
    if (hasPrice) {
      setIsPaymentModalOpen(true);
      return;
    }
    
    // Free event confirmation
    rsvp.mutate({ id: event.id, status });
  };

  return (
    <div className="container mx-auto px-4 py-8 max-w-4xl">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <h1 className="text-3xl font-bold">{event.title}</h1>
            <div className="flex gap-2">
              <button
                onClick={() => setIsInviteModalOpen(true)}
                className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium flex items-center gap-1 hover:bg-blue-200"
              >
                <UserPlus className="w-4 h-4" />
                Invite
              </button>
              <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                event.status === 'upcoming' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              }`}>
                {event.status.toUpperCase()}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div className="space-y-3">
              <div className="flex items-center text-gray-600 dark:text-gray-300">
                <Calendar className="w-5 h-5 mr-3" />
                <div>
                  <p className="font-medium">Starts: {new Date(event.starts_at).toLocaleString()}</p>
                  <p className="text-sm">Ends: {new Date(event.ends_at).toLocaleString()}</p>
                </div>
              </div>
              <div className="flex items-center text-gray-600 dark:text-gray-300">
                <MapPin className="w-5 h-5 mr-3" />
                <span>{event.location_name}</span>
              </div>
              <div className="flex items-center text-gray-600 dark:text-gray-300">
                <Users className="w-5 h-5 mr-3" />
                <span>{event.attendees_count} {event.max_attendees ? `/ ${event.max_attendees}` : ''} attending</span>
              </div>
              {event.price && Number(event.price) > 0 && (
                <div className="flex items-center text-gray-600 dark:text-gray-300">
                  <DollarSign className="w-5 h-5 mr-3" />
                  <span>${Number(event.price).toFixed(2)}</span>
                </div>
              )}
              {event.token_cost && Number(event.token_cost) > 0 && (
                <div className="flex items-center text-gray-600 dark:text-gray-300">
                  <Coins className="w-5 h-5 mr-3 text-purple-500" />
                  <span className="text-purple-600 font-semibold">{Number(event.token_cost)} Tokens</span>
                </div>
              )}
            </div>
          </div>

          <div className="mb-8">
            <h2 className="text-xl font-semibold mb-2">About this event</h2>
            <p className="text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{event.description}</p>
          </div>

          <div className="border-t pt-6">
            <h2 className="text-xl font-semibold mb-4">RSVP</h2>
            <div className="flex gap-4">
              <button
                onClick={() => handleRsvp('attending')}
                className="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
              >
                Attend
              </button>
              <button
                onClick={() => handleRsvp('maybe')}
                className="px-6 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600"
              >
                Maybe
              </button>
              <button
                onClick={() => handleRsvp('declined')}
                className="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
              >
                Decline
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <InviteUserModal
        eventId={event.id}
        isOpen={isInviteModalOpen}
        onClose={() => setIsInviteModalOpen(false)}
      />
      
      <EventPaymentModal
        event={event}
        isOpen={isPaymentModalOpen}
        onClose={() => setIsPaymentModalOpen(false)}
      />
    </div>
  );
}
