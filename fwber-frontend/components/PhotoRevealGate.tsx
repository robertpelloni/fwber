'use client'

import { RelationshipTier, getVisiblePhotoCount } from '@/lib/relationshipTiers'
import { Lock, Unlock, MessageCircle, Heart } from 'lucide-react'
import Image from 'next/image'

interface Photo {
  id: string
  url: string
  isPrimary: boolean
  type: 'ai' | 'real'
}

interface PhotoRevealGateProps {
  photos: Photo[]
  currentTier: RelationshipTier
  messagesExchanged?: number
  daysConnected?: number
  onUnlockClick?: () => void
  className?: string
}

export default function PhotoRevealGate({
  photos,
  currentTier,
  messagesExchanged = 0,
  daysConnected = 0,
  onUnlockClick,
  className = ''
}: PhotoRevealGateProps) {
  const realPhotos = photos.filter(p => p.type === 'real')
  const aiPhotos = photos.filter(p => p.type === 'ai')
  
  const visibility = getVisiblePhotoCount(currentTier, realPhotos.length)
  
  // Separate photos by visibility status
  const visibleRealPhotos = realPhotos.slice(0, visibility.real)
  const blurredRealPhotos = realPhotos.slice(visibility.real, visibility.real + visibility.blurred)
  const lockedRealPhotos = realPhotos.slice(visibility.real + visibility.blurred)
  
  // Determine what's needed for next unlock
  const getUnlockMessage = () => {
    if (currentTier === RelationshipTier.DISCOVERY) {
      return 'Match to unlock real photos'
    }
    if (currentTier === RelationshipTier.MATCHED) {
      const messagesNeeded = Math.max(0, 10 - messagesExchanged)
      return messagesNeeded > 0 
        ? `Send ${messagesNeeded} more ${messagesNeeded === 1 ? 'message' : 'messages'} to unlock clear photos`
        : 'Unlocking more photos...'
    }
    if (currentTier === RelationshipTier.CONNECTED) {
      const messagesNeeded = Math.max(0, 50 - messagesExchanged)
      const daysNeeded = Math.max(0, 7 - daysConnected)
      if (messagesNeeded > 0 && daysNeeded > 0) {
        return `${messagesNeeded} more messages & ${daysNeeded} more days to unlock full gallery`
      }
      if (messagesNeeded > 0) {
        return `${messagesNeeded} more messages to unlock full gallery`
      }
      if (daysNeeded > 0) {
        return `${daysNeeded} more days to unlock full gallery`
      }
      return 'Unlocking full gallery...'
    }
    if (currentTier === RelationshipTier.ESTABLISHED) {
      return 'Meet in person to unlock verified access'
    }
    return 'All photos unlocked!'
  }

  return (
    <div className={className}>
      <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
        {/* AI Photos - Always visible */}
        {aiPhotos.map((photo) => (
          <div key={photo.id} className="relative aspect-square rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
            <Image
              src={photo.url}
              alt="AI generated photo"
              fill
              className="object-cover"
            />
            <div className="absolute top-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
              AI
            </div>
          </div>
        ))}

        {/* Visible Real Photos */}
        {visibleRealPhotos.map((photo) => (
          <div key={photo.id} className="relative aspect-square rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
            <Image
              src={photo.url}
              alt="Photo"
              fill
              className="object-cover"
            />
            {photo.isPrimary && (
              <div className="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">
                Primary
              </div>
            )}
          </div>
        ))}

        {/* Blurred Real Photos */}
        {blurredRealPhotos.map((photo, index) => (
          <div 
            key={photo.id} 
            className="relative aspect-square rounded-lg overflow-hidden cursor-pointer group bg-gray-200 dark:bg-gray-800"
            onClick={onUnlockClick}
          >
            <Image
              src={photo.url}
              alt="Blurred photo"
              fill
              className="object-cover blur-xl scale-110"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-center justify-center">
              <div className="text-center text-white">
                <Unlock className="w-8 h-8 mx-auto mb-2 group-hover:scale-110 transition-transform" />
                <p className="text-xs font-semibold">Send messages</p>
                <p className="text-xs opacity-80">to unlock</p>
              </div>
            </div>
          </div>
        ))}

        {/* Locked Real Photos */}
        {lockedRealPhotos.map((photo, index) => (
          <div 
            key={photo.id} 
            className="relative aspect-square rounded-lg bg-gray-800 flex items-center justify-center cursor-pointer group"
            onClick={onUnlockClick}
          >
            <div className="text-center text-gray-400">
              <Lock className="w-8 h-8 mx-auto mb-2 group-hover:scale-110 transition-transform" />
              <p className="text-xs font-semibold">Locked</p>
            </div>
          </div>
        ))}
      </div>

      {/* Unlock Progress Message */}
      {currentTier !== RelationshipTier.VERIFIED && (lockedRealPhotos.length > 0 || blurredRealPhotos.length > 0) && (
        <div className="mt-6 p-4 rounded-lg bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 border border-purple-200 dark:border-purple-700">
          <div className="flex items-start gap-3">
            <div className="flex-shrink-0 mt-0.5">
              {currentTier === RelationshipTier.DISCOVERY ? (
                <Heart className="w-5 h-5 text-pink-600 dark:text-pink-400" />
              ) : (
                <MessageCircle className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              )}
            </div>
            <div className="flex-1">
              <p className="font-semibold text-sm text-purple-900 dark:text-purple-100">
                {getUnlockMessage()}
              </p>
              <p className="text-xs text-purple-700 dark:text-purple-300 mt-1">
                {lockedRealPhotos.length + blurredRealPhotos.length} {lockedRealPhotos.length + blurredRealPhotos.length === 1 ? 'photo' : 'photos'} waiting to be unlocked
              </p>
            </div>
          </div>
        </div>
      )}

      {/* All Unlocked Message */}
      {currentTier === RelationshipTier.VERIFIED && (
        <div className="mt-6 p-4 rounded-lg bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-200 dark:border-green-700">
          <div className="flex items-center gap-3">
            <Unlock className="w-5 h-5 text-green-600 dark:text-green-400" />
            <p className="font-semibold text-sm text-green-900 dark:text-green-100">
              All photos unlocked! You have complete access.
            </p>
          </div>
        </div>
      )}
    </div>
  )
}
