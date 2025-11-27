'use client'

import { useEffect, useState } from 'react'
import Image from 'next/image'
import { X, Clock } from 'lucide-react'
import PhotoRevealGate from './PhotoRevealGate'
import SecurePhotoReveal from './SecurePhotoReveal'
import { RelationshipTier } from '@/lib/relationshipTiers'
import { useFeatureFlag } from '@/lib/hooks/use-feature-flags'
import { PresenceIndicator, usePresenceContext } from './realtime/PresenceComponents'

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
  const { isEnabled: faceRevealEnabled } = useFeatureFlag('face_reveal')
  const { onlineUsers, lastSeen } = usePresenceContext()
  
  // Check if user is online
  const isUserOnline = onlineUsers.has(user.id)
  const userLastSeen = lastSeen.get(user.id)

  // Format last seen time
  const formatLastSeen = (timestamp: number) => {
    const now = Date.now()
    const diff = now - timestamp
    const minutes = Math.floor(diff / 60000)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)
    
    if (minutes < 1) return 'Just now'
    if (minutes < 60) return `${minutes}m ago`
    if (hours < 24) return `${hours}h ago`
    if (days < 7) return `${days}d ago`
    return new Date(timestamp).toLocaleDateString()
  }

  useEffect(() => {
    if (isOpen) {
      setIsVisible(true)
    } else {
      const timer = setTimeout(() => setIsVisible(false), 300)
      return () => clearTimeout(timer)
    }
  }, [isOpen])

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
              userId={user.id} 
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
                {!isUserOnline && userLastSeen && (
                  <>
                    <span>•</span>
                    <span className="flex items-center gap-1">
                      <Clock className="w-3 h-3" />
                      Last seen {formatLastSeen(userLastSeen)}
                    </span>
                  </>
                )}
              </div>
            </div>
          </div>
          <button 
            onClick={onClose}
            className="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
            aria-label="Close profile view"
            title="Close"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Body */}
        <div className="p-6 overflow-y-auto max-h-[80vh]">
          {user.profile?.bio && (
            <div className="mb-6">
              <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">About</h3>
              <p className="text-gray-800 dark:text-gray-200">{user.profile.bio}</p>
            </div>
          )}

          <div className="mb-6">
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Photos</h3>
            {faceRevealEnabled ? (
              <PhotoRevealGate
                photos={photos}
                currentTier={RelationshipTier.MATCHED} // Assume matched since we are chatting
                messagesExchanged={messagesExchanged}
                daysConnected={1} // Mock
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
