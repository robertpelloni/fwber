'use client';

import React, { useState, useEffect } from 'react';
import Image from 'next/image';
import { api } from '@/lib/api/client';
import { X, Search, UserPlus, Check } from 'lucide-react';

interface User {
  id: number;
  name: string;
  avatar_url?: string;
}

interface InviteUserModalProps {
  eventId: number;
  isOpen: boolean;
  onClose: () => void;
}

export default function InviteUserModal({ eventId, isOpen, onClose }: InviteUserModalProps) {
  const [matches, setMatches] = useState<User[]>([]);
  const [loading, setLoading] = useState(false);
  const [inviting, setInviting] = useState<number | null>(null);
  const [invited, setInvited] = useState<number[]>([]);

  useEffect(() => {
    if (isOpen) {
      fetchMatches();
    }
  }, [isOpen]);

  const fetchMatches = async () => {
    setLoading(true);
    try {
      const response = await api.get<User[]>('/matches/established');
      setMatches(response);
    } catch (error) {
      console.error('Failed to fetch matches', error);
    } finally {
      setLoading(false);
    }
  };

  const handleInvite = async (userId: number) => {
    setInviting(userId);
    try {
      await api.post(`/events/${eventId}/invite`, { user_id: userId });
      setInvited([...invited, userId]);
    } catch (error) {
      console.error('Failed to invite user', error);
      alert('Failed to invite user');
    } finally {
      setInviting(null);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md p-6 relative">
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          aria-label="Close"
        >
          <X className="w-6 h-6" />
        </button>

        <h2 className="text-xl font-bold mb-4">Invite Friends</h2>

        <div className="mb-4 relative">
          <input
            type="text"
            placeholder="Search matches..."
            className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
          />
          <Search className="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
        </div>

        <div className="max-h-60 overflow-y-auto space-y-2">
          {loading ? (
            <div className="text-center py-4">Loading...</div>
          ) : matches.length === 0 ? (
            <div className="text-center py-4 text-gray-500">No matches found to invite.</div>
          ) : (
            matches.map((user) => (
              <div key={user.id} className="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-gray-200 rounded-full overflow-hidden relative">
                    {user.avatar_url ? (
                      <Image 
                        src={user.avatar_url} 
                        alt={user.name} 
                        width={40} 
                        height={40} 
                        className="object-cover" 
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-gray-500">
                        {user.name.charAt(0)}
                      </div>
                    )}
                  </div>
                  <span className="font-medium">{user.name}</span>
                </div>
                <button
                  onClick={() => handleInvite(user.id)}
                  disabled={inviting === user.id || invited.includes(user.id)}
                  className={`p-2 rounded-full ${
                    invited.includes(user.id)
                      ? 'bg-green-100 text-green-600'
                      : 'bg-blue-100 text-blue-600 hover:bg-blue-200'
                  }`}
                  aria-label={invited.includes(user.id) ? "Invited" : "Invite"}
                >
                  {invited.includes(user.id) ? (
                    <Check className="w-5 h-5" />
                  ) : inviting === user.id ? (
                    <div className="w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
                  ) : (
                    <UserPlus className="w-5 h-5" />
                  )}
                </button>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
