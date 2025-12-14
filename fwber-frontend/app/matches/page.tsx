'use client';

import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Heart, X, Star, MessageCircle, MapPin, Info } from 'lucide-react';
import { api } from '@/lib/api/client';
import { useToast } from '@/lib/hooks/use-toast';
import { useRouter } from 'next/navigation';
import MatchFilter from '@/components/MatchFilter';
import Image from 'next/image';
import ProfileViewModal from '@/components/ProfileViewModal';

export default function MatchesPage() {
  const [matches, setMatches] = useState<any[]>([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [loading, setLoading] = useState(true);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const { success, error, ToastContainer } = useToast();
  const router = useRouter();

  const fetchMatches = useCallback(async (filters = {}) => {
    try {
      setLoading(true);
      const response = await api.get('/matches', { params: filters }) as any;
      setMatches(response.data.matches || []);
      setCurrentIndex(0);
    } catch (err) {
      console.error('Error fetching matches:', err);
      error('Failed to load matches');
    } finally {
      setLoading(false);
    }
  }, [error]);

  useEffect(() => {
    fetchMatches();
  }, [fetchMatches]);

  const handleAction = async (action: 'like' | 'pass' | 'super_like') => {
    if (!matches[currentIndex]) return;

    const targetUserId = matches[currentIndex].id;
    
    // Optimistic update
    const nextIndex = currentIndex + 1;
    setCurrentIndex(nextIndex);

    try {
      const response = await api.post('/matches/action', {
        target_user_id: targetUserId,
        action,
      }) as any;

      if (response.data.is_match) {
        success(`You matched with ${matches[currentIndex].name}!`);
        // Optionally show match modal
      }
    } catch (err) {
      console.error('Error performing match action:', err);
      // Revert optimistic update if needed, or just show error
      error('Failed to perform action');
    }
  };

  const handleFilterChange = (filters: any) => {
    fetchMatches(filters);
  };

  if (loading) {
    return <div className="flex justify-center items-center h-screen">Loading matches...</div>;
  }

  if (matches.length === 0 || currentIndex >= matches.length) {
    return (
      <div className="container mx-auto p-4 max-w-md text-center">
        <h2 className="text-2xl font-bold mb-4">No more matches</h2>
        <p className="text-gray-500 mb-6">Check back later or adjust your filters.</p>
        <Button onClick={() => fetchMatches()}>Refresh</Button>
      </div>
    );
  }

  const currentMatch = matches[currentIndex];

  return (
    <div className="container mx-auto p-4 max-w-md">
      <ToastContainer />
      <div className="mb-4">
        <MatchFilter onFilterChange={handleFilterChange} />
      </div>

      <Card className="overflow-hidden h-[600px] relative">
        <div className="h-full relative">
          {/* Image Area */}
          <div 
            className="h-3/4 bg-gray-200 relative cursor-pointer"
            onClick={() => setIsProfileOpen(true)}
          >
            {currentMatch.avatarUrl ? (
              <Image 
                src={currentMatch.avatarUrl} 
                alt={currentMatch.name} 
                fill
                className="object-cover"
                priority
              />
            ) : (
              <div className="flex items-center justify-center h-full">
                <span className="text-4xl text-gray-400">{currentMatch.name?.charAt(0)}</span>
              </div>
            )}
          </div>
          
          <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white h-1/2 flex flex-col justify-end pointer-events-none">
            <div className="flex justify-between items-end">
              <div>
                <h2 className="text-3xl font-bold flex items-center gap-2">
                  {currentMatch.name}, {currentMatch.age}
                  {currentMatch.is_verified && <span className="text-blue-400 text-sm">✓</span>}
                </h2>
                <div className="flex items-center gap-1 text-gray-200 mb-2">
                  <MapPin className="h-4 w-4" />
                  <span>{currentMatch.distance} miles away</span>
                </div>
              </div>
              <Button
                size="icon"
                variant="ghost"
                className="text-white hover:bg-white/20 pointer-events-auto mb-2"
                onClick={(e) => {
                  e.stopPropagation();
                  setIsProfileOpen(true);
                }}
              >
                <Info className="h-6 w-6" />
              </Button>
            </div>
            <p className="line-clamp-2 mb-16">{currentMatch.bio}</p>
          </div>

          {/* Action Buttons */}
          <div className="absolute bottom-6 left-0 right-0 flex justify-center gap-6 pointer-events-auto">
            <Button
              size="icon"
              variant="outline"
              className="h-14 w-14 rounded-full border-2 border-red-500 text-red-500 hover:bg-red-50 shadow-lg hover:scale-110 transition-transform"
              onClick={() => handleAction('pass')}
            >
              <X className="h-8 w-8" />
            </Button>
            
            <Button
              size="icon"
              variant="outline"
              className="h-10 w-10 rounded-full border-2 border-blue-400 text-blue-400 hover:bg-blue-50 mt-2 shadow-lg hover:scale-110 transition-transform"
              onClick={() => handleAction('super_like')}
            >
              <Star className="h-5 w-5" />
            </Button>

            <Button
              size="icon"
              variant="outline"
              className="h-14 w-14 rounded-full border-2 border-green-500 text-green-500 hover:bg-green-50 shadow-lg hover:scale-110 transition-transform"
              onClick={() => handleAction('like')}
            >
              <Heart className="h-8 w-8" />
            </Button>
          </div>
        </div>
      </Card>

      {isProfileOpen && (
        <ProfileViewModal
          isOpen={isProfileOpen}
          onClose={() => setIsProfileOpen(false)}
          user={{
            id: currentMatch.id,
            profile: {
              display_name: currentMatch.name,
              age: currentMatch.age,
              bio: currentMatch.bio,
              photos: currentMatch.photos || (currentMatch.avatarUrl ? [{
                id: 1,
                url: currentMatch.avatarUrl,
                is_private: false,
                is_primary: true
              }] : [])
            }
          }}
          messagesExchanged={0} // Discovery phase
        />
      )}
    </div>
  );
}
