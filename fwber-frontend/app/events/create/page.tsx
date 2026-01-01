'use client';

import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { useCreateEvent } from '@/lib/hooks/use-events';
import { useMyGroups, useConnectedGroups } from '@/lib/hooks/use-groups';
import { useRouter } from 'next/navigation';
import { MapPin, Users } from 'lucide-react';
// import { Checkbox } from "@/components/ui/checkbox" // Not using UI component for simplicity in prototype form
// import { Label } from "@/components/ui/label"
// import { Card, CardContent } from "@/components/ui/card"

export default function CreateEventPage() {
  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm();
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

  const { data: myGroupsData } = useMyGroups();
  const [availableGroups, setAvailableGroups] = useState<any[]>([]);
  
  // We need to fetch connected groups for each group I own/admin
  // This is a bit complex in a single hook call if I have multiple groups.
  // Ideally, I select which "Group" is hosting the event, then select connected groups.
  // Or I just select from ANY group I am an admin of + their connections.
  
  // Simplified Flow:
  // 1. Fetch my groups where I am admin
  // 2. Allow user to select "Hosting Group" (Optional) - if selected, event is associated with that group (future feature)
  // 3. Allow user to select "Shared with" groups (My groups + Connected groups of my groups)

  // For this prototype, let's just fetch ALL connected groups for ALL my admin groups.
  // This might be heavy, so let's just list "My Groups" for now as a start, 
  // and maybe later add a "Host as Group" selector which then loads connections.
  
  // Let's implement: "Select Groups to Share With"
  // We will list the user's own groups first. 
  // To get connected groups, we'd need to loop through my groups and fetch their connections.
  // For MVP, let's just allow sharing with "My Groups". 
  
  // WAIT, the prompt said "Connected Groups".
  // So I should pick a "Host Group" first.
  const [selectedHostGroupId, setSelectedHostGroupId] = useState<number | null>(null);
  
  // Get connected groups for the selected host group
  // We can't conditionally call hooks, but we can pass null to disable it
  // We need a new hook or just use existing one with enabled flag?
  // useConnectedGroups expects a groupId.
  
  // We need a custom component or logic to fetch connected groups when host group changes.
  // Let's just use the hook and pass the selected ID, defaulting to 0 or null (if hook supports it).
  // The hook: export function useConnectedGroups(groupId: number) { ... enabled: !!groupId ... }
  
  // Refactor: We need a component to handle the "Host Group" selection and then fetch connections.
  // But hooks must be at top level.
  
  // Let's try this:
  // 1. User selects a Host Group from "My Groups" (where role is admin/owner)
  // 2. We use that ID in `useConnectedGroups(selectedHostGroupId)`
  // 3. We display those connected groups as options to share with.

  const myAdminGroups = myGroupsData?.data.filter(g => g.user_role === 'admin' || g.user_role === 'owner') || [];
  
  const { data: connectedGroups } = useConnectedGroups(selectedHostGroupId || 0);

  const onSubmit = (data: any) => {
    const payload = {
      ...data,
      latitude: parseFloat(data.latitude),
      longitude: parseFloat(data.longitude),
      max_attendees: data.max_attendees ? parseInt(data.max_attendees) : undefined,
      price: data.price ? parseFloat(data.price) : undefined,
      token_cost: data.token_cost ? parseInt(data.token_cost) : undefined,
      shared_group_ids: data.shared_group_ids ? data.shared_group_ids.map((id: string) => parseInt(id)) : [],
    };
    
    // If a host group was selected, maybe we should include it in shared_group_ids too?
    // Or add a new field 'hosting_group_id' to event?
    // The current backend implementation just attaches groups.
    // So if I host as Group A, I should probably attach Group A.
    if (selectedHostGroupId) {
        if (!payload.shared_group_ids.includes(selectedHostGroupId)) {
            payload.shared_group_ids.push(selectedHostGroupId);
        }
    }

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

        {/* Host Group Selection */}
        {myAdminGroups.length > 0 && (
            <div className="bg-muted/30 p-4 rounded-lg border">
                <label className="block text-sm font-medium mb-2">Host as Group (Optional)</label>
                <select 
                    className="w-full border rounded-md p-2 mb-2"
                    onChange={(e) => setSelectedHostGroupId(e.target.value ? parseInt(e.target.value) : null)}
                >
                    <option value="">Post as Individual</option>
                    {myAdminGroups.map(group => (
                        <option key={group.id} value={group.id}>{group.name}</option>
                    ))}
                </select>
                <p className="text-xs text-muted-foreground">Select a group to enable sharing with connected groups.</p>

                {/* Connected Groups Selection */}
                {selectedHostGroupId && connectedGroups && connectedGroups.length > 0 && (
                    <div className="mt-4">
                        <label className="block text-sm font-medium mb-2">Share with Connected Groups</label>
                        <div className="space-y-2">
                            {connectedGroups.map(group => (
                                <div key={group.id} className="flex items-center space-x-2">
                                    <input 
                                        type="checkbox" 
                                        value={group.id} 
                                        {...register('shared_group_ids')}
                                        id={`group-${group.id}`}
                                        className="h-4 w-4"
                                    />
                                    <label htmlFor={`group-${group.id}`} className="text-sm cursor-pointer flex items-center gap-2">
                                        <span>{group.name}</span>
                                        <span className="text-xs text-muted-foreground">({group.member_count} members)</span>
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        )}

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
