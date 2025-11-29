'use client';

import React, { useState } from 'react';
import { useWhoLikesYou, usePremiumStatus } from '@/lib/hooks/use-premium';
import { PremiumUpgradeModal } from '@/components/PremiumUpgradeModal';
import { Button } from '@/components/ui/button';
import { Lock } from 'lucide-react';

export default function WhoLikesYouPage() {
  const { data: premiumStatus, isLoading: isLoadingStatus } = usePremiumStatus();
  const { data: likers, isLoading: isLoadingLikers } = useWhoLikesYou();
  const [showUpgradeModal, setShowUpgradeModal] = useState(false);

  if (isLoadingStatus) return <div className="p-8 text-center">Loading...</div>;

  const isPremium = premiumStatus?.is_premium;

  if (!isPremium) {
    return (
      <div className="container mx-auto p-4 max-w-4xl">
        <h1 className="text-2xl font-bold mb-6">Who Likes You</h1>
        
        <div className="bg-white rounded-xl shadow-lg p-8 text-center border border-gray-100">
          <div className="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <Lock className="w-10 h-10 text-yellow-600" />
          </div>
          
          <h2 className="text-2xl font-bold mb-2">See who likes you!</h2>
          <p className="text-gray-500 mb-8 max-w-md mx-auto">
            Upgrade to Gold to see the full list of people who have already swiped right on you. Match instantly!
          </p>
          
          <div className="grid grid-cols-3 sm:grid-cols-4 gap-4 mb-8 opacity-50 blur-sm select-none pointer-events-none">
            {[...Array(8)].map((_, i) => (
              <div key={i} className="aspect-[3/4] bg-gray-200 rounded-lg"></div>
            ))}
          </div>

          <Button 
            onClick={() => setShowUpgradeModal(true)}
            className="bg-gradient-to-r from-yellow-500 to-yellow-700 text-white font-bold px-8 py-6 text-lg rounded-full shadow-lg hover:shadow-xl transition-all"
          >
            Unlock Gold to See
          </Button>
        </div>

        <PremiumUpgradeModal 
          isOpen={showUpgradeModal} 
          onClose={() => setShowUpgradeModal(false)} 
        />
      </div>
    );
  }

  return (
    <div className="container mx-auto p-4 max-w-4xl">
      <h1 className="text-2xl font-bold mb-6">Who Likes You ({likers?.length || 0})</h1>
      
      {isLoadingLikers ? (
        <div className="text-center py-12">Loading your admirers...</div>
      ) : likers?.length === 0 ? (
        <div className="text-center py-12 text-gray-500">
          No new likes yet. Keep your profile updated!
        </div>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          {likers?.map((user: any) => (
            <div key={user.id} className="relative group overflow-hidden rounded-xl shadow-md">
              <div className="aspect-[3/4] relative">
                 <img 
                   src={user.photos?.[0]?.url || user.avatar_url || '/placeholder-user.jpg'} 
                   alt={user.name}
                   className="object-cover w-full h-full"
                 />
                 <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3 text-white">
                   <p className="font-bold">{user.name}, {user.age}</p>
                 </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
