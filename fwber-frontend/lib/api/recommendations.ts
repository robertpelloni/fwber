import { apiClient } from './client';

export interface Recommendation {
  id: string;
  type: 'content' | 'collaborative' | 'ai' | 'location';
  content: {
    id: string;
    title?: string;
    description?: string;
    name?: string;
    distance?: number;
    created_at?: string;
    [key: string]: any;
  };
  score: number;
  reason: string;
  source?: string;
  sources?: string[];
}

export interface RecommendationMetadata {
  total: number;
  types: string[];
  context: Record<string, any>;
  generated_at: string;
  cache_hit: boolean;
}

export interface RecommendationResponse {
  recommendations: Recommendation[];
  metadata: RecommendationMetadata;
}

export interface TrendingContent {
  id: string;
  type: string;
  title: string;
  description: string;
  engagement_score: number;
  trending_since: string;
}

export interface TrendingResponse {
  trending: TrendingContent[];
  metadata: {
    timeframe: string;
    total: number;
    generated_at: string;
  };
}

export interface FeedItem {
  id: string;
  type: 'recommendation' | 'activity' | 'trending';
  content: any;
  score?: number;
  reason?: string;
  timestamp?: string;
}

export interface FeedResponse {
  feed: FeedItem[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    has_more: boolean;
  };
  metadata: {
    generated_at: string;
    user_id: number;
  };
}

export interface RecommendationFeedback {
  recommendation_id: string;
  action: 'click' | 'like' | 'dislike' | 'share' | 'ignore';
  content_id: string;
  rating?: number;
  feedback_text?: string;
}

export interface RecommendationAnalytics {
  total_recommendations: number;
  click_through_rate: number;
  user_satisfaction: number;
  top_performing_types: string[];
  improvement_suggestions: string[];
}

export interface RecommendationAnalyticsResponse {
  analytics: RecommendationAnalytics;
  timeframe: string;
  generated_at: string;
}

/**
 * Get personalized recommendations for the authenticated user
 */
export async function getRecommendations(params?: {
  limit?: number;
  types?: string[];
  context?: {
    latitude?: number;
    longitude?: number;
    radius?: number;
    [key: string]: any;
  };
}): Promise<RecommendationResponse> {
  const response = await apiClient.get<RecommendationResponse>('/recommendations', { params });
  return response.data;
}

/**
 * Get recommendations for a specific type
 */
export async function getRecommendationsByType(
  type: string,
  params?: {
    limit?: number;
    context?: Record<string, any>;
  }
): Promise<RecommendationResponse> {
  const response = await apiClient.get<RecommendationResponse>(`/recommendations/type/${type}`, { params });
  return response.data;
}

/**
 * Get trending recommendations
 */
export async function getTrendingRecommendations(params?: {
  limit?: number;
  timeframe?: '24h' | '7d' | '30d';
}): Promise<TrendingResponse> {
  const response = await apiClient.get<TrendingResponse>('/recommendations/trending', { params });
  return response.data;
}

/**
 * Get personalized feed for the user
 */
export async function getPersonalizedFeed(params?: {
  page?: number;
  per_page?: number;
}): Promise<FeedResponse> {
  const response = await apiClient.get<FeedResponse>('/recommendations/feed', { params });
  return response.data;
}

/**
 * Provide feedback on recommendations
 */
export async function submitRecommendationFeedback(
  feedback: RecommendationFeedback
): Promise<{ message: string; feedback_id: string }> {
  const response = await apiClient.post<{ message: string; feedback_id: string }>('/recommendations/feedback', feedback);
  return response.data;
}

/**
 * Get recommendation analytics (admin only)
 */
export async function getRecommendationAnalytics(params?: {
  timeframe?: string;
}): Promise<RecommendationAnalyticsResponse> {
  const response = await apiClient.get<RecommendationAnalyticsResponse>('/recommendations/analytics', { params });
  return response.data;
}

/**
 * Get recommendations with caching
 */
export async function getCachedRecommendations(
  cacheKey: string,
  params?: {
    limit?: number;
    types?: string[];
    context?: Record<string, any>;
  }
): Promise<RecommendationResponse> {
  // Check localStorage cache first
  const cached = localStorage.getItem(`recommendations_${cacheKey}`);
  if (cached) {
    const data = JSON.parse(cached);
    const cacheAge = Date.now() - data.timestamp;
    const cacheTTL = 30 * 60 * 1000; // 30 minutes
    
    if (cacheAge < cacheTTL) {
      return data.response;
    }
  }

  // Fetch fresh recommendations
  const response = await getRecommendations(params);
  
  // Cache the response
  localStorage.setItem(`recommendations_${cacheKey}`, JSON.stringify({
    response,
    timestamp: Date.now(),
  }));

  return response;
}

/**
 * Clear recommendation cache
 */
export function clearRecommendationCache(): void {
  const keys = Object.keys(localStorage);
  keys.forEach(key => {
    if (key.startsWith('recommendations_')) {
      localStorage.removeItem(key);
    }
  });
}

/**
 * Get recommendation cache info
 */
export function getRecommendationCacheInfo(): {
  cacheKeys: string[];
  totalSize: number;
} {
  const keys = Object.keys(localStorage);
  const recommendationKeys = keys.filter(key => key.startsWith('recommendations_'));
  
  let totalSize = 0;
  recommendationKeys.forEach(key => {
    const value = localStorage.getItem(key);
    if (value) {
      totalSize += value.length;
    }
  });

  return {
    cacheKeys: recommendationKeys,
    totalSize,
  };
}
