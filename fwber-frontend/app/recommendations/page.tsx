"use client";

import { useState, useEffect } from 'react';
import { useAuth } from '@/lib/auth-context';
import { useRouter } from 'next/navigation';
import {
  useRecommendations,
  useTrendingRecommendations,
  usePersonalizedFeed,
  useRecommendationFeedback,
  useAIRecommendations,
  useLocationRecommendations,
  useCollaborativeRecommendations,
  useContentRecommendations,
  useMixedRecommendations,
  useRecommendationCache,
} from '@/lib/hooks/use-recommendations';
import { useLocation } from '@/lib/hooks/use-location';

export default function RecommendationsPage() {
  const { user, isAuthenticated, loading } = useAuth();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('mixed');
  const [userLocation, setUserLocation] = useState<{ latitude: number; longitude: number } | null>(null);
  const { clearCache, refreshRecommendations } = useRecommendationCache();

  // Get user's current location for location-based recommendations
  useEffect(() => {
    if (navigator.geolocation && isAuthenticated) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setUserLocation({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
          });
        },
        (error) => {
          console.warn('Geolocation error:', error);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
      );
    }
  }, [isAuthenticated]);

  // Recommendation queries
  const { data: mixedRecommendations, isLoading: isLoadingMixed } = useMixedRecommendations({
    limit: 10,
    context: userLocation ? {
      latitude: userLocation.latitude,
      longitude: userLocation.longitude,
      radius: 5000,
    } : undefined,
    enabled: isAuthenticated,
  });

  const { data: aiRecommendations, isLoading: isLoadingAI } = useAIRecommendations({
    limit: 5,
    context: userLocation ? {
      latitude: userLocation.latitude,
      longitude: userLocation.longitude,
    } : undefined,
    enabled: isAuthenticated,
  });

  const { data: locationRecommendations, isLoading: isLoadingLocation } = useLocationRecommendations({
    limit: 5,
    latitude: userLocation?.latitude,
    longitude: userLocation?.longitude,
    radius: 5000,
    enabled: isAuthenticated && !!userLocation,
  });

  const { data: collaborativeRecommendations, isLoading: isLoadingCollaborative } = useCollaborativeRecommendations({
    limit: 5,
    enabled: isAuthenticated,
  });

  const { data: contentRecommendations, isLoading: isLoadingContent } = useContentRecommendations({
    limit: 5,
    enabled: isAuthenticated,
  });

  const { data: trendingRecommendations, isLoading: isLoadingTrending } = useTrendingRecommendations({
    limit: 5,
    timeframe: '24h',
    enabled: isAuthenticated,
  });

  const { data: personalizedFeed, isLoading: isLoadingFeed } = usePersonalizedFeed({
    page: 1,
    per_page: 20,
    enabled: isAuthenticated,
  });

  const feedbackMutation = useRecommendationFeedback();

  useEffect(() => {
    if (!loading && !isAuthenticated) {
      router.push('/login');
    }
  }, [isAuthenticated, loading, router]);

  const handleRecommendationAction = async (
    recommendationId: string,
    action: 'click' | 'like' | 'dislike' | 'share' | 'ignore',
    contentId: string,
    rating?: number
  ) => {
    try {
      await feedbackMutation.mutateAsync({
        recommendation_id: recommendationId,
        action,
        content_id: contentId,
        rating,
      });
      
      // Show success feedback
      console.log('Feedback submitted successfully');
    } catch (error) {
      console.error('Failed to submit feedback:', error);
    }
  };

  const handleClearCache = () => {
    clearCache();
    refreshRecommendations();
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen text-white">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-red-500"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  const renderRecommendationCard = (recommendation: any, index: number) => (
    <div key={recommendation.id || index} className="bg-gray-800 p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
      <div className="flex justify-between items-start mb-4">
        <div className="flex-1">
          <h3 className="text-xl font-bold text-white mb-2">
            {recommendation.content.title || recommendation.content.name || `Recommendation ${index + 1}`}
          </h3>
          <p className="text-gray-300 mb-3">
            {recommendation.content.description || recommendation.reason}
          </p>
          <div className="flex items-center space-x-4 text-sm text-gray-400">
            <span className="bg-red-600 px-2 py-1 rounded text-white text-xs">
              {recommendation.type.toUpperCase()}
            </span>
            <span>Score: {(recommendation.score * 100).toFixed(1)}%</span>
            {recommendation.content.distance && (
              <span>{Math.round(recommendation.content.distance)}m away</span>
            )}
          </div>
        </div>
        <div className="flex space-x-2">
          <button
            onClick={() => handleRecommendationAction(
              recommendation.id,
              'like',
              recommendation.content.id,
              5
            )}
            className="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors"
          >
            👍
          </button>
          <button
            onClick={() => handleRecommendationAction(
              recommendation.id,
              'dislike',
              recommendation.content.id,
              1
            )}
            className="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
          >
            👎
          </button>
          <button
            onClick={() => handleRecommendationAction(
              recommendation.id,
              'share',
              recommendation.content.id
            )}
            className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
          >
            📤
          </button>
        </div>
      </div>
    </div>
  );

  const getCurrentRecommendations = () => {
    switch (activeTab) {
      case 'mixed':
        return mixedRecommendations?.recommendations || [];
      case 'ai':
        return aiRecommendations?.recommendations || [];
      case 'location':
        return locationRecommendations?.recommendations || [];
      case 'collaborative':
        return collaborativeRecommendations?.recommendations || [];
      case 'content':
        return contentRecommendations?.recommendations || [];
      case 'trending':
        return trendingRecommendations?.trending || [];
      case 'feed':
        return personalizedFeed?.feed || [];
      default:
        return [];
    }
  };

  const getCurrentLoading = () => {
    switch (activeTab) {
      case 'mixed':
        return isLoadingMixed;
      case 'ai':
        return isLoadingAI;
      case 'location':
        return isLoadingLocation;
      case 'collaborative':
        return isLoadingCollaborative;
      case 'content':
        return isLoadingContent;
      case 'trending':
        return isLoadingTrending;
      case 'feed':
        return isLoadingFeed;
      default:
        return false;
    }
  };

  return (
    <div className="min-h-screen bg-gray-900 text-white p-8">
      <div className="max-w-6xl mx-auto">
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-4xl font-bold text-red-500">AI-Powered Recommendations</h1>
          <div className="flex space-x-4">
            <button
              onClick={handleClearCache}
              className="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors"
            >
              Clear Cache
            </button>
            <button
              onClick={refreshRecommendations}
              className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition-colors"
            >
              Refresh
            </button>
          </div>
        </div>

        {/* Location Status */}
        {userLocation ? (
          <div className="bg-green-900 border border-green-600 p-4 rounded-lg mb-6">
            <p className="text-green-200">
              📍 Location enabled: {userLocation.latitude.toFixed(4)}, {userLocation.longitude.toFixed(4)}
            </p>
          </div>
        ) : (
          <div className="bg-yellow-900 border border-yellow-600 p-4 rounded-lg mb-6">
            <p className="text-yellow-200">
              ⚠️ Location not available - some recommendations may be limited
            </p>
          </div>
        )}

        {/* Tab Navigation */}
        <div className="mb-8 border-b border-gray-700">
          <nav className="-mb-px flex space-x-8" aria-label="Tabs">
            {[
              { id: 'mixed', label: 'Mixed', description: 'All recommendation types' },
              { id: 'ai', label: 'AI-Powered', description: 'AI-generated recommendations' },
              { id: 'location', label: 'Location', description: 'Nearby recommendations' },
              { id: 'collaborative', label: 'Collaborative', description: 'Based on similar users' },
              { id: 'content', label: 'Content', description: 'Content-based filtering' },
              { id: 'trending', label: 'Trending', description: 'Popular right now' },
              { id: 'feed', label: 'Feed', description: 'Personalized feed' },
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === tab.id
                    ? 'border-red-500 text-red-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
                title={tab.description}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        {/* Recommendations Content */}
        <div className="space-y-6">
          {getCurrentLoading() ? (
            <div className="flex justify-center items-center py-12">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500"></div>
              <span className="ml-4 text-gray-400">Loading recommendations...</span>
            </div>
          ) : (
            <>
              {getCurrentRecommendations().length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {getCurrentRecommendations().map((recommendation, index) => 
                    renderRecommendationCard(recommendation, index)
                  )}
                </div>
              ) : (
                <div className="text-center py-12">
                  <div className="text-gray-400 text-lg mb-4">
                    No recommendations available for this category
                  </div>
                  <p className="text-gray-500">
                    Try refreshing or check back later for new recommendations.
                  </p>
                </div>
              )}
            </>
          )}
        </div>

        {/* Metadata */}
        {activeTab === 'mixed' && mixedRecommendations?.metadata && (
          <div className="mt-8 bg-gray-800 p-4 rounded-lg">
            <h3 className="text-lg font-semibold mb-2">Recommendation Metadata</h3>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span className="text-gray-400">Total:</span>
                <span className="ml-2 text-white">{mixedRecommendations.metadata.total}</span>
              </div>
              <div>
                <span className="text-gray-400">Types:</span>
                <span className="ml-2 text-white">{mixedRecommendations.metadata.types.join(', ')}</span>
              </div>
              <div>
                <span className="text-gray-400">Generated:</span>
                <span className="ml-2 text-white">
                  {new Date(mixedRecommendations.metadata.generated_at).toLocaleTimeString()}
                </span>
              </div>
              <div>
                <span className="text-gray-400">Cache Hit:</span>
                <span className={`ml-2 ${mixedRecommendations.metadata.cache_hit ? 'text-green-400' : 'text-red-400'}`}>
                  {mixedRecommendations.metadata.cache_hit ? 'Yes' : 'No'}
                </span>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
