'use client';

import { useState, useEffect } from 'react';
import Image from 'next/image';
import { useParams, useSearchParams } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { getPublicProfile, type UserProfile } from '@/lib/api/profile';
import { performMatchAction } from '@/lib/api/matches';
import { api } from '@/lib/api/client';
import { PresenceIndicator } from '@/components/realtime';

export default function PublicProfilePage() {
  const { id } = useParams();
  const searchParams = useSearchParams();
  const wingmanId = searchParams.get('wingman');
  const { token, user } = useAuth();
  
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionPerformed, setActionPerformed] = useState(false);

  useEffect(() => {
    if (token && id) {
      loadProfile();
    }
  }, [token, id]);

  useEffect(() => {
    // Record Wingman Assist if present
    if (token && wingmanId && id && !actionPerformed) {
      recordAssist();
    }
  }, [token, wingmanId, id]);

  const loadProfile = async () => {
    try {
      setIsLoading(true);
      const data = await getPublicProfile(token!, Number(id));
      setProfile(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load profile');
    } finally {
      setIsLoading(false);
    }
  };

  const recordAssist = async () => {
    try {
      await api.post('/wingman/assist', {
        subject_id: Number(id),
        wingman_id: Number(wingmanId),
      });
      console.log('Wingman assist recorded');
    } catch (err) {
      console.error('Failed to record assist', err);
    }
  };

  const handleAction = async (action: 'like' | 'pass') => {
    if (!token || !profile) return;
    
    try {
      await performMatchAction(profile.id, action);
      setActionPerformed(true);
      if (action === 'like') {
        alert('Liked! If they like you back, it\'s a match!');
      } else {
        alert('Passed.');
      }
    } catch (err) {
      alert('Failed to perform action');
    }
  };

  if (isLoading) return <div className="flex justify-center p-8">Loading...</div>;
  if (error) return <div className="text-red-500 p-8">{error}</div>;
  if (!profile) return <div className="p-8">Profile not found</div>;

  const p = profile.profile;

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div className="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden">
          {/* Photo */}
          <div className="relative h-96 w-full bg-gray-200">
            {p.photos?.[0] ? (
              <Image
                src={p.photos[0].url}
                alt={p.display_name || 'User'}
                fill
                className="object-cover"
              />
            ) : (
              <div className="flex items-center justify-center h-full text-4xl text-gray-400">
                {p.display_name?.[0]}
              </div>
            )}
            
            {wingmanId && (
              <div className="absolute top-4 left-4 bg-purple-600 text-white px-4 py-2 rounded-full shadow-lg animate-bounce">
                🧚 Wingman Recommended!
              </div>
            )}
          </div>

          {/* Info */}
          <div className="p-8">
            <div className="flex justify-between items-start mb-6">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  {p.display_name}, {p.age}
                  <PresenceIndicator userId={String(profile.id)} />
                </h1>
                <p className="text-gray-600 dark:text-gray-400">
                  {p.location?.city}, {p.location?.state}
                </p>
              </div>
            </div>

            <div className="prose dark:prose-invert mb-8">
              <h3 className="text-lg font-semibold mb-2">About</h3>
              <p>{p.bio || 'No bio yet.'}</p>
            </div>

            {/* Actions */}
            {!actionPerformed ? (
              <div className="flex gap-4 justify-center">
                <button
                  onClick={() => handleAction('pass')}
                  className="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-full font-semibold hover:bg-gray-300 transition-colors"
                >
                  Pass
                </button>
                <button
                  onClick={() => handleAction('like')}
                  className="px-8 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-full font-semibold hover:opacity-90 transition-opacity shadow-lg"
                >
                  Like
                </button>
              </div>
            ) : (
              <div className="text-center text-gray-500 italic">
                You have already acted on this profile.
              </div>
            )}
          </div>
        </div>
      </div>
    </ProtectedRoute>
  );
}
