'use client'

import { useState } from 'react'
import { RelationshipTier } from '@/lib/relationshipTiers'
import RelationshipTierBadge from '@/components/RelationshipTierBadge'
import PhotoRevealGate from '@/components/PhotoRevealGate'
import TierUpgradeNotification from '@/components/TierUpgradeNotification'

// Force dynamic rendering
export const dynamic = 'force-dynamic'

// Mock data
const mockPhotos = [
  { id: '1', url: '/api/placeholder/400/400', isPrimary: true, type: 'ai' as const },
  { id: '2', url: '/api/placeholder/400/400', isPrimary: false, type: 'ai' as const },
  { id: '3', url: '/api/placeholder/400/400', isPrimary: false, type: 'real' as const },
  { id: '4', url: '/api/placeholder/400/400', isPrimary: false, type: 'real' as const },
  { id: '5', url: '/api/placeholder/400/400', isPrimary: false, type: 'real' as const },
  { id: '6', url: '/api/placeholder/400/400', isPrimary: false, type: 'real' as const },
  { id: '7', url: '/api/placeholder/400/400', isPrimary: false, type: 'real' as const },
]

export default function TierDemoPage() {
  const [currentTier, setCurrentTier] = useState<RelationshipTier>(RelationshipTier.DISCOVERY)
  const [messagesExchanged, setMessagesExchanged] = useState(0)
  const [daysConnected, setDaysConnected] = useState(0)
  const [hasMetInPerson, setHasMetInPerson] = useState(false)
  const [showUpgradeNotification, setShowUpgradeNotification] = useState(false)
  const [previousTier, setPreviousTier] = useState<RelationshipTier>(RelationshipTier.DISCOVERY)

  const handleTierChange = (newTier: RelationshipTier) => {
    if (newTier > currentTier) {
      setPreviousTier(currentTier)
      setCurrentTier(newTier)
      setShowUpgradeNotification(true)
    } else {
      setCurrentTier(newTier)
    }
  }

  const tierLabels = {
    [RelationshipTier.DISCOVERY]: 'Discovery',
    [RelationshipTier.MATCHED]: 'Matched',
    [RelationshipTier.CONNECTED]: 'Connected',
    [RelationshipTier.ESTABLISHED]: 'Established',
    [RelationshipTier.VERIFIED]: 'Verified'
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-950 p-6">
      <div className="max-w-6xl mx-auto">
        <h1 className="text-3xl font-bold mb-2 text-gray-900 dark:text-white">Relationship Tier System Demo</h1>
        <p className="text-gray-600 dark:text-gray-400 mb-8">
          Test the progressive photo reveal system
        </p>

        {/* Controls */}
        <div className="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-6 mb-6">
          <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">Controls</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Tier selector */}
            <div>
              <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                Relationship Tier
              </label>
              <div className="flex flex-wrap gap-2">
                {Object.entries(tierLabels).map(([tier, label]) => (
                  <button
                    key={tier}
                    onClick={() => handleTierChange(Number(tier) as unknown as RelationshipTier)}
                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                      currentTier === (Number(tier) as unknown as RelationshipTier)
                        ? 'bg-purple-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                    }`}
                  >
                    {label}
                  </button>
                ))}
              </div>
            </div>

            {/* Metrics */}
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                  Messages Exchanged: {messagesExchanged}
                </label>
                <input
                  type="range"
                  min="0"
                  max="100"
                  value={messagesExchanged}
                  onChange={(e) => setMessagesExchanged(Number(e.target.value))}
                  className="w-full"
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                  Days Connected: {daysConnected}
                </label>
                <input
                  type="range"
                  min="0"
                  max="30"
                  value={daysConnected}
                  onChange={(e) => setDaysConnected(Number(e.target.value))}
                  className="w-full"
                />
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="metInPerson"
                  checked={hasMetInPerson}
                  onChange={(e) => setHasMetInPerson(e.target.checked)}
                  className="w-4 h-4"
                />
                <label htmlFor="metInPerson" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Met in person
                </label>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Tier Badges */}
          <div className="lg:col-span-1 space-y-4">
            <div>
              <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">Current Status</h2>
              <RelationshipTierBadge
                tier={currentTier}
                messagesExchanged={messagesExchanged}
                daysConnected={daysConnected}
                hasMetInPerson={hasMetInPerson}
                showProgress={true}
              />
            </div>

            <div>
              <h3 className="text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Compact View:</h3>
              <RelationshipTierBadge
                tier={currentTier}
                compact={true}
              />
            </div>
          </div>

          {/* Photo Gallery */}
          <div className="lg:col-span-2">
            <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">Photo Gallery</h2>
            <PhotoRevealGate
              photos={mockPhotos}
              currentTier={currentTier}
              messagesExchanged={messagesExchanged}
              daysConnected={daysConnected}
              onUnlockClick={() => {
                alert('In the real app, this would open a chat or prompt to send a message!')
              }}
            />
          </div>
        </div>

        {/* All Tiers Overview */}
        <div className="mt-8">
          <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">All Tiers</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {Object.entries(tierLabels).map(([tier]) => (
              <RelationshipTierBadge
                key={tier}
                tier={Number(tier) as unknown as RelationshipTier}
                messagesExchanged={messagesExchanged}
                daysConnected={daysConnected}
                hasMetInPerson={hasMetInPerson}
                showProgress={false}
              />
            ))}
          </div>
        </div>
      </div>

      {/* Upgrade Notification */}
      {showUpgradeNotification && (
        <TierUpgradeNotification
          previousTier={previousTier}
          newTier={currentTier}
          onClose={() => setShowUpgradeNotification(false)}
        />
      )}
    </div>
  )
}
