'use client';

import { useState, useEffect, useCallback } from 'react';
import Image from 'next/image';
import { useParams, useSearchParams } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import ProtectedRoute from '@/components/ProtectedRoute';
import { getPublicProfile, type UserProfile } from '@/lib/api/profile';
import { performMatchAction } from '@/lib/api/matches';
import { api } from '@/lib/api/client';
import { PresenceIndicator } from '@/components/realtime/PresenceComponents';
import TipButton from '@/components/tipping/TipButton';
import PhotoRevealGate from '@/components/PhotoRevealGate';
import { RelationshipTier } from '@/lib/relationshipTiers';
import { photoAPI } from '@/lib/api/photos';
import GiftShopModal from '@/components/gifts/GiftShopModal';
import { Gift, ShieldCheck } from 'lucide-react';
import { VouchBadge } from '@/components/profile/VouchBadge';
import { useToast } from '@/components/ToastProvider';

export default function PublicProfilePage() {
  const params = useParams();
  const searchParams = useSearchParams();
  const wingmanId = searchParams.get('wingman');
  const { token, user } = useAuth();
  
  // Safely extract id - useParams returns string | string[] | undefined
  const id = typeof params.id === 'string' ? params.id : Array.isArray(params.id) ? params.id[0] : null;
  const numericId = id ? parseInt(id, 10) : NaN;
  
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionPerformed, setActionPerformed] = useState(false);
  const [isGiftModalOpen, setIsGiftModalOpen] = useState(false);
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

  const recordAssist = useCallback(async () => {
    try {
      await api.post('/wingman/assist', {
        subject_id: Number(id),
        wingman_id: Number(wingmanId),
      });
      console.log('Wingman assist recorded');
    } catch (err) {
      console.error('Failed to record assist', err);
    }
  }, [id, wingmanId]);

  useEffect(() => {
    if (token && id) {
      loadProfile();
    }
  }, [token, id, loadProfile]);

  useEffect(() => {
    // Record Wingman Assist if present
    if (token && wingmanId && id && !actionPerformed) {
      recordAssist();
    }
  }, [token, wingmanId, id, actionPerformed, recordAssist]);

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

  const handleTokenUnlock = async (photoId: string) => {
    try {
      const res = await photoAPI.unlockPhoto(photoId);
      if (res.success) {
        showSuccess('Photo Unlocked!', res.message);
        loadProfile(); // Refresh
      }
    } catch (e: any) {
      showError('Unlock Failed', e.message || 'Unlock failed');
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
          {/* Main Photo & Header */}
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
              <div className="absolute top-4 left-4 bg-purple-600 text-white px-4 py-2 rounded-full shadow-lg animate-bounce z-10">
                🧚 Wingman Recommended!
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
                          {p.location?.city}, {p.location?.state}
                        </p>
                    </div>
                    <TipButton recipientId={profile.id} recipientName={p.display_name || 'User'} />
                 </div>
            </div>
          </div>

          {/* Body */}
          <div className="p-8">

            {(p.vouches && p.vouches.length > 0) && (
                 <div className="flex gap-2 mb-6 flex-wrap">
                    <VouchBadge type="safe" count={p.vouches.filter((v: any) => v.type === 'safe').length} />
                    <VouchBadge type="fun" count={p.vouches.filter((v: any) => v.type === 'fun').length} />
                    <VouchBadge type="hot" count={p.vouches.filter((v: any) => v.type === 'hot').length} />
                 </div>
            )}
              <div>
                <div className="flex justify-between items-center w-full">
                  <div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                      {p.display_name}, {p.age}
                      <PresenceIndicator userId={String(profile.id)} />
                    </h1>
                    
                    {/* Vouches Summary */}
                    {(p.vouches && p.vouches.length > 0) && (
                         <div className="flex gap-2 mt-2 flex-wrap">
                            <VouchBadge type="safe" count={p.vouches.filter((v: any) => v.type === 'safe').length} />
                            <VouchBadge type="fun" count={p.vouches.filter((v: any) => v.type === 'fun').length} />
                            <VouchBadge type="hot" count={p.vouches.filter((v: any) => v.type === 'hot').length} />
                         </div>
                    )}

                    <p className="text-gray-600 dark:text-gray-400 mt-1">
                      {p.location?.city}, {p.location?.state}
                    </p>
                  </div>
                  <TipButton recipientId={profile.id} recipientName={p.display_name || 'User'} />
                </div>
              </div>

            <div className="prose dark:prose-invert mb-8">
              <h3 className="text-lg font-semibold mb-2">About</h3>
              <p>{p.bio || 'No bio yet.'}</p>
            </div>

            {/* Photo Gate */}
            <div className="mb-8">
                <h3 className="text-lg font-semibold mb-4">Photos</h3>
                <PhotoRevealGate
                    photos={p.photos?.map(ph => ({
                        id: String(ph.id),
                        url: ph.url,
                        isPrimary: ph.is_primary,
                        type: 'real',
                        isPrivate: ph.is_private,
                        isUnlocked: ph.is_unlocked,
                        unlockPrice: ph.unlock_price
                    })) || []}
                    currentTier={RelationshipTier.DISCOVERY}
                    onTokenUnlock={handleTokenUnlock}
                />
            </div>

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
                
                <button
                  onClick={() => setIsGiftModalOpen(true)}
                  className="flex items-center gap-2 px-6 py-2 bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 rounded-full font-medium hover:bg-pink-200 dark:hover:bg-pink-900/50 transition-colors"
                >
                  <Gift className="w-4 h-4" />
                  Send Gift
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

      {profile && (
        <GiftShopModal
          isOpen={isGiftModalOpen}
          onClose={() => setIsGiftModalOpen(false)}
          receiverId={profile.id}
          receiverName={profile.profile.display_name || 'User'}
        />
      )}
    </ProtectedRoute>
  );
}
