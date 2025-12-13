'use client'

import { useEffect, useState } from 'react'
import Image from 'next/image'
import { X, Clock, CheckCircle, UserCheck, Share2 } from 'lucide-react'
import PhotoRevealGate from './PhotoRevealGate'
import SecurePhotoReveal from './SecurePhotoReveal'
import { RelationshipTier } from '@/lib/relationshipTiers'
import { useFeatureFlag } from '@/lib/hooks/use-feature-flags'
import { PresenceIndicator, usePresenceContext } from './realtime/PresenceComponents'
import { useRelationshipTier } from '@/lib/hooks/useRelationshipTier'
import { apiClient } from '@/lib/api/client'
import { useToast } from '@/components/ToastProvider'

interface ProfileViewModalProps {
  isOpen: boolean
  onClose: () => void
  user: {
    id: number
    profile?: {
      display_name: string | null
      age: number | null
      bio?: string
      photos?: Array<{
        id: number
        url: string
        is_private: boolean
        is_primary: boolean
      }>
    }
  }
  messagesExchanged: number
  matchId?: number
}

export default function ProfileViewModal({ isOpen, onClose, user, messagesExchanged, matchId }: ProfileViewModalProps) {
  const [isVisible, setIsVisible] = useState(false)
  const [isUnlockedViaShare, setIsUnlockedViaShare] = useState(false)
  const { isEnabled: faceRevealEnabled } = useFeatureFlag('face_reveal')
  const { onlineUsers } = usePresenceContext()
  const { showSuccess, showError } = useToast()
  
  const { 
    tierData,
    isLoading: tierLoading,
    incrementMessages,
    markAsMetInPerson
  } = useRelationshipTier(user.id, matchId || null)

  const currentTier = tierData?.tier
  const userConfirmedMeeting = tierData?.userConfirmedMeeting
  const otherUserConfirmedMeeting = tierData?.otherUserConfirmedMeeting
  
  // Check if user is online
  const isUserOnline = onlineUsers?.some(u => u.user_id === user.id.toString() && u.status === 'online')

  useEffect(() => {
    if (isOpen) {
      setIsVisible(true)
      // Check if unlocked via share
      apiClient.get<{ unlocked: boolean }>(`/share-unlock/${user.id}`)
        .then(res => setIsUnlockedViaShare(res.data.unlocked))
        .catch(() => setIsUnlockedViaShare(false))
    } else {
      const timer = setTimeout(() => setIsVisible(false), 300)
      return () => clearTimeout(timer)
    }
  }, [isOpen, user.id])

  const handleShare = async () => {
    try {
      // Generate link (mock for now, or use real wingman link)
      const link = `${window.location.origin}/profile/${user.id}?ref=${user.id}` // In real app, use current user ID as ref
      await navigator.clipboard.writeText(link)
      
      // Record share
      await apiClient.post('/share-unlock', {
        target_profile_id: user.id,
        platform: 'copy_link'
      })
      
      setIsUnlockedViaShare(true)
      
      showSuccess(
        "Link copied!",
        "Profile unlocked! Share this link with a friend."
      )
    } catch (error) {
      console.error('Share failed', error)
      showError(
        "Share failed",
        "Could not copy link."
      )
    }
  }

  if (!isVisible && !isOpen) return null

  // Map API photos to PhotoRevealGate format
  const photos = user.profile?.photos?.map(p => ({
    id: p.id.toString(),
    url: p.url,
    isPrimary: p.is_primary,
    // Assume private photos are 'real' and public are 'ai' for now, 
    // or just treat all as 'real' if we want to test the blur logic on everything.
    // In a real app, we'd have a clear distinction.
    // For testing Face Reveal, we want 'real' photos to be blurred.
    type: 'real' as const 
  })) || []

  // Simple photo grid for when Face Reveal is disabled
  const SimplePhotoGrid = () => (
    <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
      {user.profile?.photos?.map((photo) => (
        <div key={photo.id} className="relative aspect-square rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
          <Image
            src={photo.url}
            alt="Photo"
            fill
            className="object-cover"
          />
          {photo.is_primary && (
            <div className="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">
              Primary
            </div>
          )}
        </div>
      ))}
      {(!user.profile?.photos || user.profile.photos.length === 0) && (
        <div className="col-span-full text-center py-8 text-gray-500">
          No photos available
        </div>
      )}
    </div>
  )

  return (
    <div data-testid="profile-modal" className={`fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'opacity-0'}`}>
      {/* Backdrop */}
      <div 
        className="absolute inset-0 bg-black/80 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal Content */}
      <div className={`relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl overflow-hidden transform transition-all duration-300 ${isOpen ? 'scale-100 translate-y-0' : 'scale-95 translate-y-8'}`}>
        
        {/* Header */}
        <div className="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
          <div className="flex items-center gap-3">
            {/* Presence Indicator */}
            <PresenceIndicator 
              userId={user.id.toString()} 
              size="md" 
              showLabel 
              className="flex-shrink-0"
            />
            <div>
              <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                {user.profile?.display_name || 'User'}
              </h2>
              <div className="flex items-center gap-2 text-sm text-gray-500">
                {user.profile?.age && (
                  <span>{user.profile.age} years old</span>
                )}
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button 
              onClick={handleShare}
              className="p-2 text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 rounded-full hover:bg-purple-50 dark:hover:bg-purple-900/20"
              aria-label="Share to unlock"
              title="Share to unlock photos"
            >
              <Share2 className="w-6 h-6" />
            </button>
            <button 
              onClick={onClose}
              className="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
              aria-label="Close profile view"
              title="Close"
            >
              <X className="w-6 h-6" />
            </button>
          </div>
        </div>

        {/* Body */}
        <div className="p-6 overflow-y-auto max-h-[80vh]">
          {user.profile?.bio && (
            <div className="mb-6">
              <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">About</h3>
              <p className="text-gray-800 dark:text-gray-200">{user.profile.bio}</p>
            </div>
          )}

          {/* Meeting Confirmation Section */}
          {currentTier === RelationshipTier.ESTABLISHED && (
            <div className="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
              <h3 className="text-sm font-semibold text-purple-900 dark:text-purple-100 mb-2 flex items-center gap-2">
                <UserCheck className="w-4 h-4" />
                Verify Relationship
              </h3>
              <p className="text-sm text-purple-700 dark:text-purple-300 mb-4">
                To unlock the final tier and see all photos, both of you need to confirm that you&apos;ve met in person.
              </p>
              
              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  onClick={() => markAsMetInPerson()}
                  disabled={userConfirmedMeeting}
                  className={`flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                    userConfirmedMeeting
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 cursor-default'
                      : 'bg-purple-600 text-white hover:bg-purple-700 dark:bg-purple-600 dark:hover:bg-purple-500'
                  }`}
                >
                  {userConfirmedMeeting ? (
                    <>
                      <CheckCircle className="w-4 h-4" />
                      You confirmed meeting
                    </>
                  ) : (
                    'I met this person'
                  )}
                </button>
                
                <div className={`flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border ${
                  otherUserConfirmedMeeting
                    ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300'
                    : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400'
                }`}>
                  {otherUserConfirmedMeeting ? (
                    <>
                      <CheckCircle className="w-4 h-4" />
                      They confirmed meeting
                    </>
                  ) : (
                    <>
                      <Clock className="w-4 h-4" />
                      Waiting for them...
                    </>
                  )}
                </div>
              </div>
            </div>
          )}

          <div className="mb-6">
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Photos</h3>
            {faceRevealEnabled ? (
              <PhotoRevealGate
                photos={photos}
                currentTier={currentTier || RelationshipTier.MATCHED} 
                messagesExchanged={messagesExchanged}
                daysConnected={1} // Mock
                isUnlockedViaShare={isUnlockedViaShare}
              />
            ) : (
              <SimplePhotoGrid />
            )}
          </div>
        </div>

      </div>
    </div>
  )
}
