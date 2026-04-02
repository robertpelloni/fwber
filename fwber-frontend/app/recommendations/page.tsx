"use client";

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import {
  ArrowLeft,
  Brain,
  Compass,
  Layers,
  Loader2,
  MapPin,
  Newspaper,
  RefreshCw,
  Sparkles,
  Trash2,
  TrendingUp,
  Users,
} from 'lucide-react';

import ProtectedRoute from '@/components/ProtectedRoute';
import AppHeader from '@/components/AppHeader';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAuth } from '@/lib/auth-context';
import {
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
import type { FeedItem, Recommendation, TrendingContent } from '@/lib/api/recommendations';

type RecommendationTab =
  | 'mixed'
  | 'ai'
  | 'location'
  | 'collaborative'
  | 'content'
  | 'trending'
  | 'feed';

const TAB_CONFIG: Array<{
  id: RecommendationTab;
  label: string;
  description: string;
  icon: typeof Sparkles;
}> = [
  { id: 'mixed', label: 'Mixed', description: 'All recommendation types', icon: Layers },
  { id: 'ai', label: 'AI', description: 'AI-generated picks', icon: Brain },
  { id: 'location', label: 'Nearby', description: 'Location-aware matches', icon: MapPin },
  { id: 'collaborative', label: 'Collaborative', description: 'Based on similar users', icon: Users },
  { id: 'content', label: 'Content', description: 'Content-based suggestions', icon: Newspaper },
  { id: 'trending', label: 'Trending', description: 'Popular right now', icon: TrendingUp },
  { id: 'feed', label: 'Feed', description: 'Your personalized feed', icon: Sparkles },
];

export default function RecommendationsPage() {
  const { isAuthenticated, isLoading: authLoading } = useAuth();
  const [activeTab, setActiveTab] = useState<RecommendationTab>('mixed');
  const [userLocation, setUserLocation] = useState<{ latitude: number; longitude: number } | null>(null);
  const { clearCache, refreshRecommendations } = useRecommendationCache();
  const feedbackMutation = useRecommendationFeedback();

  useEffect(() => {
    if (!navigator.geolocation || !isAuthenticated) {
      return;
    }

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
  }, [isAuthenticated]);

  const { data: mixedRecommendations, isLoading: isLoadingMixed } = useMixedRecommendations({
    limit: 10,
    context: userLocation
      ? {
          latitude: userLocation.latitude,
          longitude: userLocation.longitude,
          radius: 5000,
        }
      : undefined,
    enabled: isAuthenticated,
  });

  const { data: aiRecommendations, isLoading: isLoadingAI } = useAIRecommendations({
    limit: 5,
    context: userLocation
      ? {
          latitude: userLocation.latitude,
          longitude: userLocation.longitude,
        }
      : undefined,
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
    } catch (error) {
      console.error('Failed to submit feedback:', error);
    }
  };

  const currentItems = useMemo(() => {
    switch (activeTab) {
      case 'mixed':
        return (mixedRecommendations?.recommendations || []) as Recommendation[];
      case 'ai':
        return (aiRecommendations?.recommendations || []) as Recommendation[];
      case 'location':
        return (locationRecommendations?.recommendations || []) as Recommendation[];
      case 'collaborative':
        return (collaborativeRecommendations?.recommendations || []) as Recommendation[];
      case 'content':
        return (contentRecommendations?.recommendations || []) as Recommendation[];
      case 'trending':
        return (trendingRecommendations?.trending || []) as TrendingContent[];
      case 'feed':
        return (personalizedFeed?.feed || []) as FeedItem[];
      default:
        return [] as Recommendation[];
    }
  }, [
    activeTab,
    aiRecommendations?.recommendations,
    collaborativeRecommendations?.recommendations,
    contentRecommendations?.recommendations,
    locationRecommendations?.recommendations,
    mixedRecommendations?.recommendations,
    personalizedFeed?.feed,
    trendingRecommendations?.trending,
  ]);

  const isCurrentLoading = useMemo(() => {
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
  }, [
    activeTab,
    isLoadingAI,
    isLoadingCollaborative,
    isLoadingContent,
    isLoadingFeed,
    isLoadingLocation,
    isLoadingMixed,
    isLoadingTrending,
  ]);

  const mixedMetadata = activeTab === 'mixed' ? mixedRecommendations?.metadata : null;
  const currentRankingStrategy = useMemo(() => {
    switch (activeTab) {
      case 'mixed':
        return mixedRecommendations?.metadata?.ranking_strategy ?? null;
      case 'ai':
        return aiRecommendations?.metadata?.ranking_strategy ?? null;
      case 'location':
        return locationRecommendations?.metadata?.ranking_strategy ?? null;
      case 'collaborative':
        return collaborativeRecommendations?.metadata?.ranking_strategy ?? null;
      case 'content':
        return contentRecommendations?.metadata?.ranking_strategy ?? null;
      case 'feed':
        return personalizedFeed?.metadata?.ranking_strategy ?? null;
      default:
        return null;
    }
  }, [
    activeTab,
    aiRecommendations?.metadata?.ranking_strategy,
    collaborativeRecommendations?.metadata?.ranking_strategy,
    contentRecommendations?.metadata?.ranking_strategy,
    locationRecommendations?.metadata?.ranking_strategy,
    mixedRecommendations?.metadata?.ranking_strategy,
    personalizedFeed?.metadata?.ranking_strategy,
  ]);

  const renderRecommendationCard = (recommendation: Recommendation, index: number) => {
    const title = recommendation.content.title || recommendation.content.name || `Recommendation ${index + 1}`;
    const description = recommendation.content.description || recommendation.reason;
    const contentId = String(recommendation.content.id || recommendation.id);

    return (
      <Card key={recommendation.id || `${activeTab}-${index}`} className="h-full border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <CardHeader className="space-y-3">
          <div className="flex items-start justify-between gap-3">
            <div className="space-y-2">
              <Badge variant="outline" className="border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200">
                {recommendation.type.toUpperCase()}
              </Badge>
              <CardTitle className="text-xl text-gray-900 dark:text-white">{title}</CardTitle>
            </div>
            <Badge variant="secondary" className="shrink-0">
              {(recommendation.score * 100).toFixed(1)}%
            </Badge>
          </div>
          <CardDescription className="text-sm text-gray-600 dark:text-gray-300">
            {description}
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {recommendation.scene_signals && (
            <div className="rounded-2xl border border-cyan-200 bg-cyan-50 p-3 dark:border-cyan-900/50 dark:bg-cyan-950/20">
              <div className="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700 dark:text-cyan-200">
                <Compass className="h-3.5 w-3.5" />
                Scene-aligned +{Math.round(recommendation.scene_signals.score_boost * 100)} pts
              </div>
              {recommendation.scene_signals.headline && (
                <p className="mb-2 text-sm text-gray-700 dark:text-gray-200">{recommendation.scene_signals.headline}</p>
              )}
              <div className="flex flex-wrap gap-2">
                {recommendation.scene_signals.matched_topics.map((topic) => (
                  <Badge key={topic.slug} variant="outline" className="border-cyan-300 bg-white text-cyan-700 dark:border-cyan-800 dark:bg-cyan-950/20 dark:text-cyan-200">
                    {topic.emoji ? `${topic.emoji} ` : ''}{topic.label}
                  </Badge>
                ))}
                {recommendation.scene_signals.matched_tags.map((tag) => (
                  <Badge key={tag} variant="secondary">#{tag}</Badge>
                ))}
              </div>
            </div>
          )}
          <div className="flex flex-wrap gap-2 text-xs text-muted-foreground">
            {recommendation.content.distance ? <Badge variant="outline">{Math.round(recommendation.content.distance)}m away</Badge> : null}
            {recommendation.source ? <Badge variant="outline">{recommendation.source}</Badge> : null}
            {recommendation.sources?.length ? <Badge variant="outline">{recommendation.sources.join(', ')}</Badge> : null}
          </div>
          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => handleRecommendationAction(recommendation.id, 'like', contentId, 5)}
            >
              Like
            </Button>
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => handleRecommendationAction(recommendation.id, 'dislike', contentId, 1)}
            >
              Pass
            </Button>
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => handleRecommendationAction(recommendation.id, 'share', contentId)}
            >
              Share
            </Button>
          </div>
        </CardContent>
      </Card>
    );
  };

  const renderTrendingCard = (item: TrendingContent, index: number) => (
    <Card key={item.id || `${activeTab}-${index}`} className="h-full border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
      <CardHeader className="space-y-3">
        <div className="flex items-start justify-between gap-3">
          <div className="space-y-2">
            <Badge variant="outline" className="border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
              {item.type}
            </Badge>
            <CardTitle className="text-xl text-gray-900 dark:text-white">{item.title}</CardTitle>
          </div>
          <Badge variant="secondary">#{index + 1}</Badge>
        </div>
        <CardDescription className="text-sm text-gray-600 dark:text-gray-300">
          {item.description}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3 text-sm text-muted-foreground">
        <div className="flex items-center justify-between gap-3">
          <span>Engagement score</span>
          <span className="font-medium text-gray-900 dark:text-white">{item.engagement_score.toFixed(1)}</span>
        </div>
        <div className="flex items-center justify-between gap-3">
          <span>Trending since</span>
          <span className="font-medium text-gray-900 dark:text-white">{new Date(item.trending_since).toLocaleString()}</span>
        </div>
      </CardContent>
    </Card>
  );

  const renderFeedCard = (item: FeedItem, index: number) => {
    const title = item.content?.title || item.content?.name || `Feed item ${index + 1}`;
    const description = item.reason || item.content?.description || 'Personalized recommendation item';

    return (
      <Card key={item.id || `${activeTab}-${index}`} className="h-full border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <CardHeader className="space-y-3">
          <div className="flex items-start justify-between gap-3">
            <div className="space-y-2">
              <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                {item.type}
              </Badge>
              <CardTitle className="text-xl text-gray-900 dark:text-white">{title}</CardTitle>
            </div>
            {typeof item.score === 'number' ? <Badge variant="secondary">{(item.score * 100).toFixed(1)}%</Badge> : null}
          </div>
          <CardDescription className="text-sm text-gray-600 dark:text-gray-300">
            {description}
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-3 text-sm text-muted-foreground">
          {item.scene_signals && (
            <div className="rounded-2xl border border-cyan-200 bg-cyan-50 p-3 dark:border-cyan-900/50 dark:bg-cyan-950/20">
              <div className="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700 dark:text-cyan-200">
                <Compass className="h-3.5 w-3.5" />
                Scene cue
              </div>
              {item.scene_signals.headline && (
                <p className="mb-2 text-sm text-gray-700 dark:text-gray-200">{item.scene_signals.headline}</p>
              )}
              <div className="flex flex-wrap gap-2">
                {item.scene_signals.matched_topics.map((topic) => (
                  <Badge key={topic.slug} variant="outline" className="border-cyan-300 bg-white text-cyan-700 dark:border-cyan-800 dark:bg-cyan-950/20 dark:text-cyan-200">
                    {topic.emoji ? `${topic.emoji} ` : ''}{topic.label}
                  </Badge>
                ))}
                {item.scene_signals.matched_tags.map((tag) => (
                  <Badge key={tag} variant="secondary">#{tag}</Badge>
                ))}
              </div>
            </div>
          )}
          {item.timestamp ? (
            <div className="flex items-center justify-between gap-3">
              <span>Updated</span>
              <span className="font-medium text-gray-900 dark:text-white">{new Date(item.timestamp).toLocaleString()}</span>
            </div>
          ) : null}
        </CardContent>
      </Card>
    );
  };

  const renderCurrentCard = (item: Recommendation | TrendingContent | FeedItem, index: number) => {
    if (activeTab === 'trending') {
      return renderTrendingCard(item as TrendingContent, index);
    }

    if (activeTab === 'feed') {
      return renderFeedCard(item as FeedItem, index);
    }

    return renderRecommendationCard(item as Recommendation, index);
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader />
        <main className="mx-auto max-w-6xl px-4 py-8 space-y-8">
          <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between gap-4 flex-wrap">
              <div>
                <h1 className="flex items-center gap-3 text-3xl font-bold text-gray-900 dark:text-white">
                  <Sparkles className="h-8 w-8 text-purple-500" />
                  Recommendations
                </h1>
                <p className="mt-1 text-gray-500 dark:text-gray-400">
                  Personalized discovery across AI, collaborative, nearby, and trending signals.
                </p>
              </div>
              <Badge variant="outline" className="border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200">
                Discovery Hub
              </Badge>
            </div>

            <div className="flex flex-wrap gap-3">
              <Button asChild variant="outline" className="border-blue-200 text-blue-700 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300 dark:hover:bg-blue-950">
                <Link href="/dashboard">
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Back Home
                </Link>
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  clearCache();
                  refreshRecommendations();
                }}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Clear Cache
              </Button>
              <Button type="button" variant="outline" onClick={refreshRecommendations}>
                <RefreshCw className="mr-2 h-4 w-4" />
                Refresh
              </Button>
            </div>
          </div>

          <Card className="border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <CardHeader>
              <CardTitle className="text-lg text-gray-900 dark:text-white">Context</CardTitle>
              <CardDescription>
                Recommendation quality improves when location and recent activity are available.
              </CardDescription>
            </CardHeader>
            <CardContent>
              {userLocation ? (
                <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                  Location enabled near {userLocation.latitude.toFixed(4)}, {userLocation.longitude.toFixed(4)}.
                </div>
              ) : (
                <div className="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-900 dark:bg-yellow-950 dark:text-yellow-200">
                  Location is unavailable, so nearby recommendations may be limited until the browser shares position.
                </div>
              )}
            </CardContent>
          </Card>

          <div className="flex flex-wrap gap-3">
            {TAB_CONFIG.map((tab) => {
              const Icon = tab.icon;
              const isActive = activeTab === tab.id;

              return (
                <button
                  key={tab.id}
                  type="button"
                  onClick={() => setActiveTab(tab.id)}
                  className={`rounded-full border px-4 py-2 text-sm font-medium transition-colors ${
                    isActive
                      ? 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200'
                      : 'border-gray-200 bg-white text-gray-600 hover:border-purple-200 hover:text-purple-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-purple-900 dark:hover:text-purple-200'
                  }`}
                  title={tab.description}
                >
                  <span className="inline-flex items-center gap-2">
                    <Icon className="h-4 w-4" />
                    {tab.label}
                  </span>
                </button>
              );
            })}
          </div>

          <section className="space-y-6">
            {currentRankingStrategy ? (
              <Card className="border-cyan-200 bg-cyan-50 dark:border-cyan-900/50 dark:bg-cyan-950/20">
                <CardContent className="space-y-3 p-6">
                  <div className="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700 dark:text-cyan-200">
                    <Compass className="h-4 w-4" />
                    Ranking strategy
                  </div>
                  <p className="text-sm text-gray-700 dark:text-gray-200">{currentRankingStrategy.summary}</p>
                  <div className="flex flex-wrap gap-2">
                    {currentRankingStrategy.base_relevance ? <Badge variant="outline">Base relevance</Badge> : null}
                    {currentRankingStrategy.trusted_connections ? <Badge variant="outline">Trusted connections</Badge> : null}
                    {currentRankingStrategy.scene_alignment ? <Badge variant="outline">Scene alignment</Badge> : null}
                    {currentRankingStrategy.freshness ? <Badge variant="outline">Freshness</Badge> : null}
                  </div>
                </CardContent>
              </Card>
            ) : null}
            {isCurrentLoading || authLoading ? (
              <div className="flex items-center justify-center py-20">
                <Loader2 className="h-8 w-8 animate-spin text-purple-500" />
              </div>
            ) : currentItems.length > 0 ? (
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                {currentItems.map((item, index) => renderCurrentCard(item, index))}
              </div>
            ) : (
              <Card className="border-dashed border-2 border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900">
                <CardContent className="py-12 text-center">
                  <p className="text-lg font-medium text-gray-900 dark:text-white">
                    No recommendations available for this category yet.
                  </p>
                  <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Try refreshing, enabling location, or checking back after more activity.
                  </p>
                </CardContent>
              </Card>
            )}
          </section>

          {mixedMetadata ? (
            <Card className="border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
              <CardHeader>
                <CardTitle className="text-lg text-gray-900 dark:text-white">Mixed recommendation metadata</CardTitle>
                <CardDescription>Live details for the current combined recommendation batch.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div className="rounded-lg border p-4">
                  <p className="text-sm text-muted-foreground">Total</p>
                  <p className="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{mixedMetadata.total}</p>
                </div>
                <div className="rounded-lg border p-4">
                  <p className="text-sm text-muted-foreground">Types</p>
                  <p className="mt-1 text-sm font-medium text-gray-900 dark:text-white">{mixedMetadata.types.join(', ')}</p>
                </div>
                <div className="rounded-lg border p-4">
                  <p className="text-sm text-muted-foreground">Generated</p>
                  <p className="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                    {new Date(mixedMetadata.generated_at).toLocaleTimeString()}
                  </p>
                </div>
                <div className="rounded-lg border p-4">
                  <p className="text-sm text-muted-foreground">Cache hit</p>
                  <p className={`mt-1 text-sm font-medium ${mixedMetadata.cache_hit ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'}`}>
                    {mixedMetadata.cache_hit ? 'Yes' : 'No'}
                  </p>
                </div>
              </CardContent>
            </Card>
          ) : null}
        </main>
      </div>
    </ProtectedRoute>
  );
}
