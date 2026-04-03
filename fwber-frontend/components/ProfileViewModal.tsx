'use client'

import { useEffect, useState } from 'react'
import Image from 'next/image'
import { X } from 'lucide-react'
import { PresenceIndicator } from './realtime/PresenceComponents'
import { useAuth } from '@/lib/auth-context'

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
}

export default function ProfileViewModal({ isOpen, onClose, user }: ProfileViewModalProps) {
  const { user: currentUser } = useAuth()
  const [isVisible, setIsVisible] = useState(false)

  useEffect(() => {
    if (isOpen) {
      setIsVisible(true)
    } else {
      const timer = setTimeout(() => setIsVisible(false), 300)
      return () => clearTimeout(timer)
    }
  }, [isOpen])

  if (!isVisible && !isOpen) return null

  const p = user.profile;
  const publicPhotos = p?.photos?.filter(photo => !photo.is_private) || [];

  return (
    <div data-testid="profile-modal" className={`fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'opacity-0'}`}>
      <div 
        className="absolute inset-0 bg-black/80 backdrop-blur-sm"
        onClick={onClose}
      />

      <div className={`relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl overflow-hidden transform transition-all duration-300 ${isOpen ? 'scale-100 translate-y-0' : 'scale-95 translate-y-8'}`}>
        
        <div className="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
          <div className="flex items-center gap-3">
            <PresenceIndicator 
              userId={user.id.toString()} 
              size="md" 
              showLabel 
              className="flex-shrink-0"
            />
            <div>
              <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                {p?.display_name || 'User'}
              </h2>
              <div className="flex items-center gap-2 text-sm text-gray-500">
                {p?.age && <span>{p.age} years old</span>}
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button 
              onClick={onClose}
              className="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
              aria-label="Close profile view"
            >
              <X className="w-6 h-6" />
            </button>
          </div>
        </div>

        <div className="p-6 overflow-y-auto max-h-[80vh]">
          {p?.bio && (
            <div className="mb-6">
              <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">About</h3>
              <p className="text-gray-800 dark:text-gray-200">{p.bio}</p>
            </div>
          )}

          <div className="mb-6">
            <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Photos</h3>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
              {publicPhotos.map((photo) => (
                <div key={photo.id} className="relative aspect-square rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
                  <Image
                    src={photo.url || '/placeholder.jpg'}
                    alt="Photo"
                    fill
                    className="object-cover"
                  />
                  {photo.is_primary && (
                    <div className="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded shadow-sm">
                      Primary
                    </div>
                  )}
                </div>
              ))}
              {publicPhotos.length === 0 && (
                <div className="col-span-full text-center py-8 text-gray-500 italic bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                  No public photos available.
                </div>
              )}
            </div>
          </div>
        </div>

      </div>
    </div>
  )
}
