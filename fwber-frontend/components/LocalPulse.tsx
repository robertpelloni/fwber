'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { useLocalPulse, useCreateProximityArtifact, useFlagProximityArtifact } from '@/lib/hooks/use-proximity';
import { 
  MapPin, 
  MessageCircle, 
  Users, 
  Heart,
  Clock,
  AlertCircle,
  Send,
  Flag,
  Megaphone,
  StickyNote,
  Sparkles,
} from 'lucide-react';
import type { ProximityArtifact, MatchCandidate, ArtifactType } from '@/types/proximity';

interface LocationState {
  latitude: number | null;
  longitude: number | null;
  error: string | null;
}

const ArtifactTypeIcon = ({ type }: { type: ArtifactType }) => {
  switch (type) {
    case 'chat':
      return <MessageCircle className="h-5 w-5" />;
    case 'board_post':
      return <StickyNote className="h-5 w-5" />;
    case 'announce':
      return <Megaphone className="h-5 w-5" />;
  }
};

const ArtifactCard = ({ 
  artifact, 
  onFlag 
}: { 
  artifact: ProximityArtifact; 
  onFlag: (id: number) => void;
}) => {
  const getTimeRemaining = (expiresAt: string) => {
    const now = new Date();
    const expires = new Date(expiresAt);
    const diff = expires.getTime() - now.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    
    if (hours > 0) return `${hours}h remaining`;
    if (minutes > 0) return `${minutes}m remaining`;
    return 'Expiring soon';
  };

  const typeColors = {
    chat: 'bg-blue-50 border-blue-200 text-blue-800',
    board_post: 'bg-green-50 border-green-200 text-green-800',
    announce: 'bg-purple-50 border-purple-200 text-purple-800',
  };

  return (
    <div className={`rounded-lg border p-4 ${typeColors[artifact.type]}`}>
      <div className="flex items-start justify-between mb-2">
        <div className="flex items-center space-x-2">
          <ArtifactTypeIcon type={artifact.type} />
          <span className="text-xs font-medium uppercase">{artifact.type.replace('_', ' ')}</span>
        </div>
        <button
          onClick={() => onFlag(artifact.id)}
          className="text-gray-400 hover:text-red-600 transition-colors"
          title="Flag for review"
        >
          <Flag className="h-4 w-4" />
        </button>
      </div>

      <p className="text-sm mb-3">{artifact.content}</p>

      <div className="flex items-center justify-between text-xs text-gray-600">
        <div className="flex items-center space-x-1">
          <MapPin className="h-3 w-3" />
          <span>{Math.round(artifact.radius)}m radius</span>
        </div>
        <div className="flex items-center space-x-1">
          <Clock className="h-3 w-3" />
          <span>{getTimeRemaining(artifact.expires_at)}</span>
        </div>
      </div>
    </div>
  );
};

const CandidateCard = ({ candidate }: { candidate: MatchCandidate }) => {
  const getCompatibilityBadges = (indicators: string[]) => {
    const badges: Record<string, { label: string; color: string }> = {
      shared_relationship_goals: { label: 'Shared Goals', color: 'bg-pink-100 text-pink-800' },
      active_locally: { label: 'Active Nearby', color: 'bg-green-100 text-green-800' },
    };

    return indicators.map(indicator => badges[indicator] || { label: indicator, color: 'bg-gray-100 text-gray-800' });
  };

  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between mb-3">
        <div>
          <div className="flex items-center space-x-2 mb-1">
            <span className="text-lg font-semibold">{candidate.age}</span>
            <span className="text-gray-500">•</span>
            <span className="text-sm text-gray-600 capitalize">{candidate.gender}</span>
          </div>
          <div className="flex items-center space-x-1 text-sm text-gray-500">
            <MapPin className="h-3 w-3" />
            <span>{candidate.distance_miles.toFixed(1)} miles away</span>
          </div>
        </div>
        <button className="bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors">
          <Heart className="h-5 w-5" />
        </button>
      </div>

      {candidate.compatibility_indicators.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {getCompatibilityBadges(candidate.compatibility_indicators).map((badge, i) => (
            <span
              key={i}
              className={`px-2 py-1 text-xs font-medium rounded-full ${badge.color}`}
            >
              {badge.label}
            </span>
          ))}
        </div>
      )}

      {candidate.last_seen && (
        <div className="mt-2 text-xs text-gray-500">
          Last seen {candidate.last_seen}
        </div>
      )}
    </div>
  );
};

export default function LocalPulse() {
  const { token } = useAuth();
  const [location, setLocation] = useState<LocationState>({
    latitude: null,
    longitude: null,
    error: null,
  });
  const [radius, setRadius] = useState(1000);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newArtifact, setNewArtifact] = useState({
    type: 'chat' as ArtifactType,
    content: '',
  });

  const createArtifact = useCreateProximityArtifact();
  const flagArtifact = useFlagProximityArtifact();

  // Get user location
  useEffect(() => {
    if (!navigator.geolocation) {
      setLocation(prev => ({ ...prev, error: 'Geolocation not supported' }));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocation({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          error: null,
        });
      },
      (error) => {
        setLocation(prev => ({
          ...prev,
          error: `Location error: ${error.message}`,
        }));
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
    );
  }, []);

  const { data: localPulse, isLoading, error, refetch } = useLocalPulse(
    {
      lat: location.latitude || 0,
      lng: location.longitude || 0,
      radius,
    },
    token,
    !!location.latitude && !!location.longitude
  );

  const handleCreateArtifact = async () => {
    if (!location.latitude || !location.longitude || !token) return;

    try {
      await createArtifact.mutateAsync({
        data: {
          type: newArtifact.type,
          content: newArtifact.content,
          lat: location.latitude,
          lng: location.longitude,
          radius,
        },
        token,
      });

      setNewArtifact({ type: 'chat', content: '' });
      setShowCreateForm(false);
      refetch();
    } catch (err) {
      console.error('Failed to create artifact:', err);
    }
  };

  const handleFlagArtifact = async (id: number) => {
    if (!token) return;
    
    try {
      await flagArtifact.mutateAsync({ id, token });
    } catch (err) {
      console.error('Failed to flag artifact:', err);
    }
  };

  if (!token) {
    return (
      <div className="text-center py-12">
        <AlertCircle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">Authentication Required</h3>
        <p className="text-gray-600">Please log in to view Local Pulse</p>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center space-x-3">
            <Sparkles className="h-8 w-8 text-purple-600" />
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Local Pulse</h1>
              <p className="text-gray-600">Discover nearby people and conversations</p>
            </div>
          </div>

          <button
            onClick={() => setShowCreateForm(!showCreateForm)}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center space-x-2"
          >
            <Send className="h-4 w-4" />
            <span>Post</span>
          </button>
        </div>

        {/* Radius Selector */}
        <div className="bg-white rounded-lg shadow-sm border p-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Search Radius
          </label>
          <select
            value={radius}
            onChange={(e) => setRadius(Number(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value={500}>500m (~0.3 miles)</option>
            <option value={1000}>1km (~0.6 miles)</option>
            <option value={2000}>2km (~1.2 miles)</option>
            <option value={5000}>5km (~3 miles)</option>
          </select>
        </div>
      </div>

      {/* Location Status */}
      {location.error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <div className="flex items-center">
            <AlertCircle className="h-5 w-5 text-red-600 mr-2" />
            <p className="text-sm text-red-600">{location.error}</p>
          </div>
        </div>
      )}

      {/* Create Artifact Form */}
      {showCreateForm && (
        <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
          <h3 className="text-lg font-semibold mb-4">Create Proximity Post</h3>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <div className="grid grid-cols-3 gap-2">
                {(['chat', 'board_post', 'announce'] as const).map((type) => (
                  <button
                    key={type}
                    onClick={() => setNewArtifact({ ...newArtifact, type })}
                    className={`p-3 rounded-lg border-2 transition-colors ${
                      newArtifact.type === type
                        ? 'border-blue-600 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <ArtifactTypeIcon type={type} />
                    <span className="text-xs block mt-1 capitalize">{type.replace('_', ' ')}</span>
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Content</label>
              <textarea
                value={newArtifact.content}
                onChange={(e) => setNewArtifact({ ...newArtifact, content: e.target.value })}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="What's happening nearby?"
                maxLength={500}
              />
              <p className="text-xs text-gray-500 mt-1">
                {newArtifact.content.length}/500 characters
              </p>
            </div>

            <div className="flex space-x-3">
              <button
                onClick={() => setShowCreateForm(false)}
                className="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200"
              >
                Cancel
              </button>
              <button
                onClick={handleCreateArtifact}
                disabled={!newArtifact.content.trim() || createArtifact.isPending}
                className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {createArtifact.isPending ? 'Posting...' : 'Post'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Loading State */}
      {isLoading && (
        <div className="text-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="text-gray-600 mt-4">Loading Local Pulse...</p>
        </div>
      )}

      {/* Error State */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <h3 className="text-sm font-medium text-red-800">Error loading Local Pulse</h3>
          <p className="text-sm text-red-600 mt-1">{error.message}</p>
        </div>
      )}

      {/* Local Pulse Content */}
      {localPulse && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Proximity Artifacts (2 columns on large screens) */}
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-xl font-semibold text-gray-900 flex items-center space-x-2">
                <MessageCircle className="h-6 w-6" />
                <span>Nearby Activity</span>
                <span className="text-sm font-normal text-gray-500">
                  ({localPulse.meta.artifacts_count})
                </span>
              </h2>
            </div>

            {localPulse.artifacts.length === 0 ? (
              <div className="text-center py-12 bg-gray-50 rounded-lg">
                <MessageCircle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No nearby activity</h3>
                <p className="text-gray-600 mb-4">
                  Be the first to post something in your area!
                </p>
                <button
                  onClick={() => setShowCreateForm(true)}
                  className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
                >
                  Create Post
                </button>
              </div>
            ) : (
              <div className="space-y-3">
                {localPulse.artifacts.map((artifact) => (
                  <ArtifactCard
                    key={artifact.id}
                    artifact={artifact}
                    onFlag={handleFlagArtifact}
                  />
                ))}
              </div>
            )}
          </div>

          {/* Match Candidates (1 column on large screens) */}
          <div className="space-y-4">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-xl font-semibold text-gray-900 flex items-center space-x-2">
                <Users className="h-6 w-6" />
                <span>Nearby Matches</span>
                <span className="text-sm font-normal text-gray-500">
                  ({localPulse.meta.candidates_count})
                </span>
              </h2>
            </div>

            {localPulse.candidates.length === 0 ? (
              <div className="text-center py-8 bg-gray-50 rounded-lg">
                <Users className="h-10 w-10 text-gray-400 mx-auto mb-3" />
                <p className="text-sm text-gray-600">
                  No compatible matches nearby
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {localPulse.candidates.map((candidate) => (
                  <CandidateCard key={candidate.user_id} candidate={candidate} />
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Meta Info */}
      {localPulse && (
        <div className="mt-8 bg-gray-50 rounded-lg p-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center text-sm">
            <div>
              <div className="font-semibold text-gray-900">{localPulse.meta.artifacts_count}</div>
              <div className="text-gray-600">Posts</div>
            </div>
            <div>
              <div className="font-semibold text-gray-900">{localPulse.meta.candidates_count}</div>
              <div className="text-gray-600">Matches</div>
            </div>
            <div>
              <div className="font-semibold text-gray-900">{(radius / 1000).toFixed(1)}km</div>
              <div className="text-gray-600">Radius</div>
            </div>
            <div>
              <div className="font-semibold text-gray-900">
                {location.latitude?.toFixed(3)}, {location.longitude?.toFixed(3)}
              </div>
              <div className="text-gray-600">Location</div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
