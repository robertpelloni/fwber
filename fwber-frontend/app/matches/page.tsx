'use client';

import { useState, useEffect, useCallback, useRef } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Heart, X, Star, MessageCircle, MapPin, Info, Mic2, Play, Pause } from 'lucide-react';
import { api } from '@/lib/api/client';
import { useToast } from '@/lib/hooks/use-toast';
import { useRouter } from 'next/navigation';
import MatchFilter from '@/components/MatchFilter';
import Image from 'next/image';
import ProfileViewModal from '@/components/ProfileViewModal';
import CreateBountyModal from '@/components/CreateBountyModal';
import BoostButton from '@/components/BoostButton';

export default function MatchesPage() {
  const [matches, setMatches] = useState<any[]>([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [loading, setLoading] = useState(true);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const [isPlaying, setIsPlaying] = useState(false);
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const { success, error, ToastContainer } = useToast();
  const router = useRouter();

  const toggleAudio = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!audioRef.current) return;
    if (isPlaying) {
        audioRef.current.pause();
    } else {
        audioRef.current.play();
    }
    setIsPlaying(!isPlaying);
  };

  const fetchMatches = useCallback(async (filters = {}) => {
    try {
      setLoading(true);
      const response = await api.get('/matches', { params: filters }) as any;
      setMatches(response.data.matches || []);
      setCurrentIndex(0);
      setIsPlaying(false);
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

    if (audioRef.current) {
        audioRef.current.pause();
        setIsPlaying(false);
    }

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
        success(`You matched with ${matches[currentIndex].name || 'Voice Only Profile'}!`);
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
        <ToastContainer />
        <h2 className="text-2xl font-bold mb-4">No more matches</h2>
        <p className="text-gray-500 mb-6">Check back later or adjust your filters.</p>
        <Button onClick={() => fetchMatches()}>Refresh</Button>
      </div>
    );
  }

  const currentMatch = matches[currentIndex];
  const isConfessional = currentMatch.is_confessional;

  return (
    <div className="container mx-auto p-4 max-w-md">
      <ToastContainer />
      <div className="mb-4 space-y-4">
        <div className="flex justify-between items-center">
            <CreateBountyModal />
            <BoostButton />
        </div>
        <MatchFilter onFilterChange={handleFilterChange} />
      </div>

      <Card className={`overflow-hidden h-[600px] relative ${isConfessional ? 'bg-zinc-950 border-purple-500/30' : ''}`}>
        <div className="h-full relative">
          {/* Image/Content Area */}
          <div 
            className={`h-3/4 relative cursor-pointer ${isConfessional ? 'bg-zinc-900 flex flex-col items-center justify-center p-8' : 'bg-gray-200'}`}
            onClick={() => !isConfessional && setIsProfileOpen(true)}
          >
            {isConfessional ? (
              <div className="flex flex-col items-center justify-center w-full h-full text-center">
                <div className="p-6 rounded-full bg-purple-500/10 border border-purple-500/20 shadow-[0_0_30px_rgba(168,85,247,0.15)] mb-6">
                    <Mic2 className="w-16 h-16 text-purple-400" />
                </div>
                <span className="px-3 py-1 bg-purple-600 text-white text-[10px] font-black uppercase tracking-widest rounded-full mb-4">
                    Confessional Mode
                </span>
                <h2 className="text-2xl font-black italic text-white tracking-tighter mb-2">
                    LISTEN TO ME
                </h2>
                <p className="text-zinc-500 text-sm leading-relaxed mb-8">
                    My profile is hidden. <br/>Vibe with my voice before we match.
                </p>

                {currentMatch.voice_intro_url ? (
                    <div className="w-full relative z-20">
                        <audio ref={audioRef} src={currentMatch.voice_intro_url} onEnded={() => setIsPlaying(false)} className="hidden" />
                        <button
                            onClick={toggleAudio}
                            className="w-full py-4 bg-white text-black rounded-2xl font-black flex items-center justify-center gap-3 hover:scale-[1.02] transition active:scale-95"
                        >
                            {isPlaying ? <Pause className="w-5 h-5 fill-current" /> : <Play className="w-5 h-5 fill-current" />}
                            {isPlaying ? 'PAUSE INTRO' : 'HEAR MY VOICE'}
                        </button>
                    </div>
                ) : (
                    <p className="text-red-400 text-sm">No voice intro available.</p>
                )}
              </div>
            ) : (
              currentMatch.avatarUrl ? (
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
              )
            )}
          </div>
          
          <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white h-1/2 flex flex-col justify-end pointer-events-none">
            <div className="flex justify-between items-end">
              <div>
                <h2 className="text-3xl font-bold flex items-center gap-2">
                  {isConfessional ? 'Voice Only Profile' : currentMatch.name}
                  {!isConfessional && currentMatch.age && <span className="text-gray-300">, {currentMatch.age}</span>}
                  {currentMatch.is_verified && !isConfessional && <span className="text-blue-400 text-sm">✓</span>}
                </h2>
                <div className="flex items-center gap-1 text-gray-200 mb-2">
                  <MapPin className="h-4 w-4" />
                  <span>{currentMatch.distance} miles away</span>
                </div>
              </div>
              {!isConfessional && (
                <Button
                  size="icon"
                  variant="ghost"
                  className="text-white hover:bg-white/20 pointer-events-auto mb-2 z-10"
                  onClick={(e) => {
                    e.stopPropagation();
                    setIsProfileOpen(true);
                  }}
                >
                  <Info className="h-6 w-6" />
                </Button>
              )}
            </div>
            {!isConfessional && <p className="line-clamp-2 mb-16">{currentMatch.bio}</p>}
            {isConfessional && (
                <div className="flex gap-4 mb-16 text-sm text-zinc-400 font-medium">
                    <span>Gender: {currentMatch.gender || '??'}</span>
                    <span>Age: {currentMatch.age || '??'}</span>
                </div>
            )}
          </div>

          {/* Action Buttons */}
          <div className="absolute bottom-6 left-0 right-0 flex justify-center gap-6 pointer-events-auto z-20">
            <Button
              size="icon"
              variant="outline"
              className="h-14 w-14 rounded-full border-2 border-red-500 text-red-500 hover:bg-red-50 shadow-lg hover:scale-110 transition-transform bg-white/10 backdrop-blur-md"
              onClick={() => handleAction('pass')}
            >
              <X className="h-8 w-8" />
            </Button>
            
            <Button
              size="icon"
              variant="outline"
              className="h-10 w-10 rounded-full border-2 border-blue-400 text-blue-400 hover:bg-blue-50 mt-2 shadow-lg hover:scale-110 transition-transform bg-white/10 backdrop-blur-md"
              onClick={() => handleAction('super_like')}
            >
              <Star className="h-5 w-5" />
            </Button>

            <Button
              size="icon"
              variant="outline"
              className="h-14 w-14 rounded-full border-2 border-green-500 text-green-500 hover:bg-green-50 shadow-lg hover:scale-110 transition-transform bg-white/10 backdrop-blur-md"
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
