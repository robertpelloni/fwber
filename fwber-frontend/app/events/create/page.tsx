'use client';

import React from 'react';
import { useForm } from 'react-hook-form';
import { useCreateEvent } from '@/lib/hooks/use-events';
import { useRouter } from 'next/navigation';
import { MapPin } from 'lucide-react';

export default function CreateEventPage() {
  const { register, handleSubmit, setValue, formState: { errors } } = useForm();
  const createEvent = useCreateEvent();
  const router = useRouter();

  const getLocation = () => {
    if (!navigator.geolocation) {
      alert('Geolocation is not supported by your browser');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setValue('latitude', position.coords.latitude);
        setValue('longitude', position.coords.longitude);
      },
      (error) => {
        alert('Unable to retrieve your location');
      }
    );
  };

  const onSubmit = (data: any) => {
    const payload = {
      ...data,
      latitude: parseFloat(data.latitude),
      longitude: parseFloat(data.longitude),
      max_attendees: data.max_attendees ? parseInt(data.max_attendees) : undefined,
      price: data.price ? parseFloat(data.price) : undefined,
      token_cost: data.token_cost ? parseInt(data.token_cost) : undefined,
    };

    createEvent.mutate(payload, {
      onSuccess: () => {
        router.push('/events');
      },
    });
  };

  return (
    <div className="container mx-auto px-4 py-8 max-w-2xl">
      <h1 className="text-2xl font-bold mb-6">Create New Event</h1>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1">Title</label>
          <input
            {...register('title', { required: 'Title is required' })}
            className="w-full border rounded-md p-2"
          />
          {errors.title && <span className="text-red-500 text-sm">{errors.title.message as string}</span>}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Description</label>
          <textarea
            {...register('description', { required: 'Description is required' })}
            className="w-full border rounded-md p-2"
            rows={4}
          />
          {errors.description && <span className="text-red-500 text-sm">{errors.description.message as string}</span>}
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Event Type</label>
          <select
            {...register('type')}
            className="w-full border rounded-md p-2"
            defaultValue="standard"
          >
            <option value="standard">Standard</option>
            <option value="speed_dating">Speed Dating</option>
            <option value="party">Party</option>
            <option value="meetup">Meetup</option>
            <option value="workshop">Workshop</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Location Name</label>
          <input
            {...register('location_name', { required: 'Location name is required' })}
            className="w-full border rounded-md p-2"
          />
          {errors.location_name && <span className="text-red-500 text-sm">{errors.location_name.message as string}</span>}
        </div>

        <div className="grid grid-cols-2 gap-4">
            <div>
                <label className="block text-sm font-medium mb-1">Latitude</label>
                <input
                    type="number"
                    step="any"
                    {...register('latitude', { required: 'Latitude is required' })}
                    className="w-full border rounded-md p-2"
                />
            </div>
            <div>
                <label className="block text-sm font-medium mb-1">Longitude</label>
                <input
                    type="number"
                    step="any"
                    {...register('longitude', { required: 'Longitude is required' })}
                    className="w-full border rounded-md p-2"
                />
            </div>
        </div>

        <div className="flex justify-end">
          <button
            type="button"
            onClick={getLocation}
            className="text-sm text-blue-600 hover:text-blue-800 flex items-center"
          >
            <MapPin className="h-4 w-4 mr-1" />
            Get Current Location
          </button>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">Start Time</label>
            <input
              type="datetime-local"
              {...register('starts_at', { required: 'Start time is required' })}
              className="w-full border rounded-md p-2"
            />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">End Time</label>
            <input
              type="datetime-local"
              {...register('ends_at', { required: 'End time is required' })}
              className="w-full border rounded-md p-2"
            />
          </div>
        </div>

        <div className="grid grid-cols-3 gap-4">
            <div>
                <label className="block text-sm font-medium mb-1">Max Attendees</label>
                <input
                    type="number"
                    {...register('max_attendees')}
                    className="w-full border rounded-md p-2"
                />
            </div>
            <div>
                <label className="block text-sm font-medium mb-1">Price (USD)</label>
                <input
                    type="number"
                    step="0.01"
                    {...register('price')}
                    className="w-full border rounded-md p-2"
                />
            </div>
            <div>
                <label className="block text-sm font-medium mb-1">Token Cost (FWB)</label>
                <input
                    type="number"
                    step="1"
                    {...register('token_cost')}
                    className="w-full border rounded-md p-2"
                />
            </div>
        </div>

        <button
          type="submit"
          disabled={createEvent.isPending}
          className="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {createEvent.isPending ? 'Creating...' : 'Create Event'}
        </button>
      </form>
    </div>
  );
}
