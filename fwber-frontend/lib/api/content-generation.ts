import { apiClient } from './client';
import { wingmanApi } from './wingman';

export interface ProfileContentRequest {
  personality?: string;
  interests?: string[];
  goals?: string;
  style?: 'casual' | 'professional' | 'humorous' | 'romantic';
  target_audience?: string;
}

export interface PostSuggestionsRequest {
  context?: {
    location?: {
      latitude: number;
      longitude: number;
      name?: string;
    };
    time?: string;
    topics?: string[];
  };
}

export interface ConversationStartersRequest {
  context?: {
    type?: 'general' | 'romantic' | 'casual' | 'professional';
    target_user?: {
      id: number;
      name: string;
      interests?: string[];
    };
    previous_messages?: string[];
    hints?: string[];
  };
}

export interface ContentOptimizationRequest {
  content: string;
  context?: {
    location?: {
      latitude: number;
      longitude: number;
      name?: string;
    };
    time?: string;
    topics?: string[];
    interests?: string[];
  };
  optimization_types?: ('engagement' | 'clarity' | 'safety' | 'relevance')[];
}

export interface ContentFeedback {
  content_id: string;
  content_type: 'profile' | 'post_suggestion' | 'conversation_starter';
  rating: number; // 1-5
  feedback?: string;
  improvements?: string[];
}

export interface GeneratedContent {
  content: string;
  provider: string;
  confidence: number;
  safety_score: number;
  type: string;
  timestamp: string;
}

export interface ContentGenerationResponse {
  success: boolean;
  data: {
    suggestions: GeneratedContent[];
    total_providers: number;
    generation_time: string;
  };
  user_id: number;
  generated_at: string;
}

export interface ContentOptimizationResponse {
  success: boolean;
  data: {
    original: string;
    optimized: string;
    improvements: Record<string, any>;
    overall_score: number;
    optimization_types: string[];
    timestamp: string;
  };
  user_id: number;
  optimized_at: string;
}

export interface GenerationStats {
  total_generations: number;
  successful_generations: number;
  failed_generations: number;
  average_generation_time: number;
  most_popular_types: string[];
  user_satisfaction: number;
  generated_at: string;
}

export interface OptimizationStats {
  total_optimizations: number;
  successful_optimizations: number;
  failed_optimizations: number;
  average_improvement_score: number;
  most_common_improvements: string[];
  optimization_types_usage: Record<string, number>;
  generated_at: string;
}

export interface GenerationHistory {
  generations: Array<{
    id: string;
    type: string;
    content: string;
    created_at: string;
    rating?: number;
  }>;
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    has_more: boolean;
  };
  generated_at: string;
}

export interface RoastProfileResponse {
  roast: string;
  share_id: string;
  is_preview?: boolean; // Add optional property here
}

export interface RoastPublicResponse {
  roast: string;
  is_preview?: boolean;
  cta?: string;
  share_id?: string;
}

/**
 * Generate a roast or hype of the authenticated user's profile.
 */
export async function roastProfile(mode: 'roast' | 'hype' = 'roast'): Promise<RoastProfileResponse> {
  return wingmanApi.roastProfile({ mode });
}

/**
 * Generate a generic roast or hype for public/guest users.
 */
export async function roastPublic(name: string, job: string, trait: string, mode: 'roast' | 'hype' = 'roast'): Promise<RoastPublicResponse> {
  return wingmanApi.roastPublic({ name, job, trait, mode });
}

/**
 * Generate personalized profile content
 */
export async function generateProfileContent(
  preferences: ProfileContentRequest
): Promise<ContentGenerationResponse> {
  const response = await apiClient.post<ContentGenerationResponse>('/content/generate-bio', preferences);
  return response.data;
}

/**
 * Generate post suggestions for a bulletin board
 */
export async function generatePostSuggestions(
  boardId: number,
  request: PostSuggestionsRequest
): Promise<ContentGenerationResponse> {
  const response = await apiClient.post<ContentGenerationResponse>(`/content/generate-posts/${boardId}`, request);
  return response.data;
}

/**
 * Generate conversation starters
 */
export async function generateConversationStarters(
  request: ConversationStartersRequest
): Promise<ContentGenerationResponse> {
  const response = await apiClient.post<ContentGenerationResponse>('/content/generate-starters', request);
  return response.data;
}

/**
 * Optimize content for better engagement, clarity, safety, and relevance
 */
export async function optimizeContent(
  request: ContentOptimizationRequest
): Promise<ContentOptimizationResponse> {
  const response = await apiClient.post<ContentOptimizationResponse>('/content-generation/optimize', request);
  return response.data;
}

/**
 * Get content generation statistics
 */
export async function getGenerationStats(): Promise<GenerationStats> {
  const response = await apiClient.get<{ data: GenerationStats }>('/content-generation/stats');
  return response.data.data;
}

/**
 * Get content optimization statistics
 */
export async function getOptimizationStats(): Promise<OptimizationStats> {
  const response = await apiClient.get<{ data: OptimizationStats }>('/content-generation/optimization-stats');
  return response.data.data;
}

/**
 * Submit feedback on generated content
 */
export async function submitContentFeedback(feedback: ContentFeedback): Promise<{
  success: boolean;
  message: string;
  feedback_id: string;
  submitted_at: string;
}> {
  const response = await apiClient.post<{
    success: boolean;
    message: string;
    feedback_id: string;
    submitted_at: string;
  }>('/content-generation/feedback', feedback);
  return response.data;
}

/**
 * Get user's content generation history
 */
export async function getGenerationHistory(params?: {
  page?: number;
  per_page?: number;
}): Promise<GenerationHistory> {
  const response = await apiClient.get<{ data: GenerationHistory }>('/content-generation/history', { params });
  return response.data.data;
}

/**
 * Delete generated content
 */
export async function deleteGeneratedContent(contentId: string): Promise<{
  success: boolean;
  message: string;
  content_id: string;
  deleted_at: string;
}> {
  const response = await apiClient.delete<{
    success: boolean;
    message: string;
    content_id: string;
    deleted_at: string;
  }>(`/content-generation/content/${contentId}`);
  return response.data;
}

/**
 * Get content suggestions with caching
 */
export async function getCachedContentSuggestions(
  cacheKey: string,
  type: 'profile' | 'post_suggestions' | 'conversation_starters',
  request: any
): Promise<ContentGenerationResponse> {
  // Check localStorage cache first
  const cached = localStorage.getItem(`content_generation_${cacheKey}`);
  if (cached) {
    const data = JSON.parse(cached);
    const cacheAge = Date.now() - data.timestamp;
    const cacheTTL = 30 * 60 * 1000; // 30 minutes
    
    if (cacheAge < cacheTTL) {
      return data.response;
    }
  }

  // Fetch fresh content
  let response: ContentGenerationResponse;
  
  switch (type) {
    case 'profile':
      response = await generateProfileContent(request);
      break;
    case 'post_suggestions':
      response = await generatePostSuggestions(request.boardId, request);
      break;
    case 'conversation_starters':
      response = await generateConversationStarters(request);
      break;
    default:
      throw new Error('Invalid content type');
  }
  
  // Cache the response
  localStorage.setItem(`content_generation_${cacheKey}`, JSON.stringify({
    response,
    timestamp: Date.now(),
  }));

  return response;
}

/**
 * Clear content generation cache
 */
export function clearContentGenerationCache(): void {
  const keys = Object.keys(localStorage);
  keys.forEach(key => {
    if (key.startsWith('content_generation_')) {
      localStorage.removeItem(key);
    }
  });
}

/**
 * Get content generation cache info
 */
export function getContentGenerationCacheInfo(): {
  cacheKeys: string[];
  totalSize: number;
} {
  const keys = Object.keys(localStorage);
  const contentGenerationKeys = keys.filter(key => key.startsWith('content_generation_'));
  
  let totalSize = 0;
  contentGenerationKeys.forEach(key => {
    const value = localStorage.getItem(key);
    if (value) {
      totalSize += value.length;
    }
  });

  return {
    cacheKeys: contentGenerationKeys,
    totalSize,
  };
}

/**
 * Analyze content quality
 */
export function analyzeContentQuality(content: string): {
  readability: number;
  engagement: number;
  clarity: number;
  safety: number;
  length: number;
  wordCount: number;
  sentenceCount: number;
} {
  const wordCount = content.split(/\s+/).length;
  const sentenceCount = content.split(/[.!?]+/).filter(s => s.trim().length > 0).length;
  const avgWordsPerSentence = sentenceCount > 0 ? wordCount / sentenceCount : 0;
  
  // Readability score (simplified Flesch Reading Ease)
  const readability = Math.max(0, Math.min(1, (206.835 - (1.015 * avgWordsPerSentence)) / 100));
  
  // Engagement score based on question marks, exclamation marks, and emojis
  const questionMarks = (content.match(/\?/g) || []).length;
  const exclamationMarks = (content.match(/!/g) || []).length;
  const emojis = (content.match(/[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F1E0}-\u{1F1FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/gu) || []).length;
  const engagement = Math.min(1, (questionMarks * 0.3 + exclamationMarks * 0.2 + emojis * 0.1) / 5);
  
  // Clarity score based on sentence length and complexity
  const clarity = Math.max(0, Math.min(1, 1 - (avgWordsPerSentence - 15) / 20));

  // Safety score (mock implementation - assumes safe unless specific keywords found)
  // In a real app, this would use a content moderation API or more sophisticated regex
  const unsafeKeywords = ['hate', 'violence', 'kill', 'attack', 'abuse'];
  const foundUnsafe = unsafeKeywords.filter(k => content.toLowerCase().includes(k)).length;
  const safety = Math.max(0, 1 - (foundUnsafe * 0.2));
  
  return {
    readability,
    engagement,
    clarity,
    safety,
    length: content.length,
    wordCount,
    sentenceCount,
  };
}

/**
 * Get content improvement suggestions
 */
export function getContentImprovementSuggestions(content: string): string[] {
  const suggestions: string[] = [];
  const analysis = analyzeContentQuality(content);
  
  if (analysis.readability < 0.5) {
    suggestions.push('Consider using shorter sentences to improve readability');
  }
  
  if (analysis.engagement < 0.3) {
    suggestions.push('Add questions or exclamation marks to increase engagement');
  }
  
  if (analysis.clarity < 0.5) {
    suggestions.push('Simplify your language for better clarity');
  }
  
  if (analysis.wordCount < 10) {
    suggestions.push('Add more details to make your content more interesting');
  }
  
  if (analysis.wordCount > 200) {
    suggestions.push('Consider shortening your content for better readability');
  }
  
  if (analysis.sentenceCount < 2) {
    suggestions.push('Break your content into multiple sentences');
  }
  
  return suggestions;
}
