'use client';

import { useState, useEffect, useCallback } from 'react';
import Image from 'next/image';
import { useParams } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { getPublicProfile, type UserProfile } from '@/lib/api/profile';
import { performMatchAction } from '@/lib/api/matches';
import { PresenceIndicator } from '@/components/realtime/PresenceComponents';
import { EvolvingAvatar } from '@/components/ui/EvolvingAvatar';
import { useToast } from '@/components/ToastProvider';

export default function PublicProfilePage() {
  const params = useParams();
  const { token } = useAuth();

  const id = typeof params.id === 'string' ? params.id : Array.isArray(params.id) ? params.id[0] : null;
  const numericId = id ? parseInt(id, 10) : NaN;

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionPerformed, setActionPerformed] = useState(false);
  const { showSuccess, showError } = useToast();

  const loadProfile = useCallback(async () => {
    if (!token || !id || isNaN(numericId)) {
      setError('Invalid profile ID');
      setIsLoading(false);
      return;
    }

    try {
      setIsLoading(true);
      const data = await getPublicProfile(token, numericId);
      setProfile(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load profile');
    } finally {
      setIsLoading(false);
    }
  }, [token, id, numericId]);

  useEffect(() => {
    if (token && id) {
      loadProfile();
    }
  }, [token, id, loadProfile]);

  const handleAction = async (action: 'like' | 'pass') => {
    if (!token || !profile) return;

    try {
      await performMatchAction(profile.id, action);
      setActionPerformed(true);
      if (action === 'like') {
        showSuccess('Liked!', 'If they like you back, it\'s a match!');
      } else {
        showSuccess('Passed', 'You won\'t see this profile again.');
      }
    } catch (err) {
      showError('Action Failed', 'Failed to perform action. Please try again.');
    }
  };

  if (isLoading) return <div className="flex justify-center p-8">Loading...</div>;
  if (error) return <div className="text-red-500 p-8">{error}</div>;
  if (!profile || !profile.profile) return <div className="p-8">Profile not found</div>;

  const p = profile.profile;
  const primaryPhoto = p.photos?.find(photo => photo.is_primary) || p.photos?.[0];
  const galleryPhotos = p.photos?.filter(photo => !photo.is_private) || [];

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div className="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden">
          
          {/* Main Photo & Header */}
          <div className="relative h-96 w-full bg-gray-200 flex justify-center items-center">
            {primaryPhoto ? (
              <EvolvingAvatar
                src={primaryPhoto.url}
                alt={p.display_name || 'User'}
                size="2xl"
                emotion={'neutral'}
                className="w-64 h-64 border-4 shadow-xl mb-4"
              />
            ) : (
              <div className="flex items-center justify-center h-full text-4xl text-gray-400">
                {p.display_name?.[0] || '?'}
              </div>
            )}

            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent" />

            <div className="absolute bottom-0 left-0 right-0 p-8 text-white">
              <div className="flex justify-between items-end">
                <div>
                  <h1 className="text-3xl font-bold flex items-center gap-2">
                    {p.display_name}, {p.age}
                    <PresenceIndicator userId={String(profile.id)} />
                  </h1>
                  <p className="text-gray-200 mt-1">
                    {p.location_name || 'Location hidden'}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Body */}
          <div className="p-8">
            <div className="prose dark:prose-invert mb-8">
              <h3 className="text-lg font-semibold mb-2">About</h3>
              <p>{p.bio || 'No bio yet.'}</p>
            </div>

            {/* Photo Gallery */}
            {galleryPhotos.length > 0 && (
              <div className="mb-8">
                <h3 className="text-lg font-semibold mb-4">Photos</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                  {galleryPhotos.map((photo) => (
                    <div key={photo.id} className="relative aspect-square rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
                      <Image
                        src={photo.url || '/placeholder.jpg'}
                        alt="Profile Photo"
                        fill
                        className="object-cover"
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Actions */}
            {!actionPerformed ? (
              <div className="flex flex-col items-center gap-4">
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
