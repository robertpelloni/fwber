import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  generateProfileContent,
  generatePostSuggestions,
  generateConversationStarters,
  optimizeContent,
  getGenerationStats,
  getOptimizationStats,
  submitContentFeedback,
  getGenerationHistory,
  deleteGeneratedContent,
  getCachedContentSuggestions,
  analyzeContentQuality,
  getContentImprovementSuggestions,
} from '@/lib/api/content-generation';

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

/**
 * Hook for generating profile content
 */
export const useProfileContentGeneration = (preferences: ProfileContentRequest) => {
  return useQuery({
    queryKey: ['profileContentGeneration', preferences],
    queryFn: () => generateProfileContent(preferences),
    enabled: Object.keys(preferences).length > 0,
    staleTime: 1000 * 60 * 30, // 30 minutes
    gcTime: 1000 * 60 * 60, // 1 hour
  });
};

/**
 * Hook for generating post suggestions
 */
export const usePostSuggestionsGeneration = (boardId: number, request: PostSuggestionsRequest) => {
  return useQuery({
    queryKey: ['postSuggestionsGeneration', boardId, request],
    queryFn: () => generatePostSuggestions(boardId, request),
    enabled: boardId > 0,
    staleTime: 1000 * 60 * 15, // 15 minutes
    gcTime: 1000 * 60 * 30, // 30 minutes
  });
};

/**
 * Hook for generating conversation starters
 */
export const useConversationStartersGeneration = (request: ConversationStartersRequest) => {
  return useQuery({
    queryKey: ['conversationStartersGeneration', request],
    queryFn: () => generateConversationStarters(request),
    staleTime: 1000 * 60 * 10, // 10 minutes
    gcTime: 1000 * 60 * 30, // 30 minutes
  });
};

/**
 * Hook for content optimization
 */
export const useContentOptimization = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (request: ContentOptimizationRequest) => optimizeContent(request),
    onSuccess: () => {
      // Invalidate related queries
      queryClient.invalidateQueries({ queryKey: ['profileContentGeneration'] });
      queryClient.invalidateQueries({ queryKey: ['postSuggestionsGeneration'] });
      queryClient.invalidateQueries({ queryKey: ['conversationStartersGeneration'] });
    },
  });
};

/**
 * Hook for content feedback
 */
export const useContentFeedback = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (feedback: {
      content_id: string;
      content_type: 'profile' | 'post_suggestion' | 'conversation_starter';
      rating: number;
      feedback?: string;
      improvements?: string[];
    }) => submitContentFeedback(feedback),
    onSuccess: () => {
      // Invalidate generation history
      queryClient.invalidateQueries({ queryKey: ['generationHistory'] });
    },
  });
};

/**
 * Hook for generation statistics
 */
export const useGenerationStats = () => {
  return useQuery({
    queryKey: ['generationStats'],
    queryFn: getGenerationStats,
    staleTime: 1000 * 60 * 5, // 5 minutes
    gcTime: 1000 * 60 * 30, // 30 minutes
  });
};

/**
 * Hook for optimization statistics
 */
export const useOptimizationStats = () => {
  return useQuery({
    queryKey: ['optimizationStats'],
    queryFn: getOptimizationStats,
    staleTime: 1000 * 60 * 5, // 5 minutes
    gcTime: 1000 * 60 * 30, // 30 minutes
  });
};

/**
 * Hook for generation history
 */
export const useGenerationHistory = (params?: { page?: number; per_page?: number }) => {
  return useQuery({
    queryKey: ['generationHistory', params],
    queryFn: () => getGenerationHistory(params),
    staleTime: 1000 * 60 * 2, // 2 minutes
    gcTime: 1000 * 60 * 10, // 10 minutes
  });
};

/**
 * Hook for deleting generated content
 */
export const useDeleteGeneratedContent = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (contentId: string) => deleteGeneratedContent(contentId),
    onSuccess: () => {
      // Invalidate generation history
      queryClient.invalidateQueries({ queryKey: ['generationHistory'] });
    },
  });
};

/**
 * Hook for cached content suggestions
 */
export const useCachedContentSuggestions = (
  cacheKey: string,
  type: 'profile' | 'post_suggestions' | 'conversation_starters',
  request: any
) => {
  return useQuery({
    queryKey: ['cachedContentSuggestions', cacheKey, type, request],
    queryFn: () => getCachedContentSuggestions(cacheKey, type, request),
    staleTime: 1000 * 60 * 30, // 30 minutes
    gcTime: 1000 * 60 * 60, // 1 hour
  });
};

/**
 * Hook for content quality analysis
 */
export const useContentQualityAnalysis = (content: string) => {
  return useQuery({
    queryKey: ['contentQualityAnalysis', content],
    queryFn: () => Promise.resolve(analyzeContentQuality(content)),
    enabled: content.length > 0,
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
};

/**
 * Hook for content improvement suggestions
 */
export const useContentImprovementSuggestions = (content: string) => {
  return useQuery({
    queryKey: ['contentImprovementSuggestions', content],
    queryFn: () => Promise.resolve(getContentImprovementSuggestions(content)),
    enabled: content.length > 0,
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
};

/**
 * Hook for smart content generation with multiple strategies
 */
export const useSmartContentGeneration = (
  type: 'profile' | 'post_suggestions' | 'conversation_starters',
  request: any,
  options?: {
    useCache?: boolean;
    cacheKey?: string;
    enableOptimization?: boolean;
  }
) => {
  const { useCache = true, cacheKey, enableOptimization = false } = options || {};
  
  // Generate content
  const contentQuery = useCachedContentSuggestions(
    cacheKey || `${type}_${JSON.stringify(request)}`,
    type,
    request
  );
  
  // Optimize content if enabled
  const optimizationMutation = useContentOptimization();
  
  const optimizeContent = async (content: string, context?: any) => {
    if (!enableOptimization) return content;
    
    try {
      const result = await optimizationMutation.mutateAsync({
        content,
        context,
        optimization_types: ['engagement', 'clarity', 'safety', 'relevance'],
      });
      return result.data.optimized;
    } catch (error) {
      console.error('Content optimization failed:', error);
      return content;
    }
  };
  
  return {
    content: contentQuery.data,
    isLoading: contentQuery.isLoading,
    error: contentQuery.error,
    optimizeContent,
    isOptimizing: optimizationMutation.isPending,
    refetch: contentQuery.refetch,
  };
};

/**
 * Hook for content generation with real-time feedback
 */
export const useContentGenerationWithFeedback = (
  type: 'profile' | 'post_suggestions' | 'conversation_starters',
  request: any
) => {
  const contentQuery = useSmartContentGeneration(type, request);
  const feedbackMutation = useContentFeedback();
  
  const submitFeedback = async (
    contentId: string,
    rating: number,
    feedback?: string,
    improvements?: string[]
  ) => {
    try {
      await feedbackMutation.mutateAsync({
        content_id: contentId,
        content_type: type === 'post_suggestions' ? 'post_suggestion' : type,
        rating,
        feedback,
        improvements,
      });
    } catch (error) {
      console.error('Feedback submission failed:', error);
    }
  };
  
  return {
    ...contentQuery,
    submitFeedback,
    isSubmittingFeedback: feedbackMutation.isPending,
  };
};

/**
 * Hook for content generation analytics
 */
export const useContentGenerationAnalytics = () => {
  const generationStats = useGenerationStats();
  const optimizationStats = useOptimizationStats();
  
  return {
    generationStats: generationStats.data,
    optimizationStats: optimizationStats.data,
    isLoading: generationStats.isLoading || optimizationStats.isLoading,
    error: generationStats.error || optimizationStats.error,
  };
};

/**
 * Hook for content generation with A/B testing
 */
export const useContentGenerationABTesting = (
  type: 'profile' | 'post_suggestions' | 'conversation_starters',
  request: any,
  variants: string[] = ['openai', 'gemini']
) => {
  const [selectedVariant, setSelectedVariant] = useState<string>(
    variants[Math.floor(Math.random() * variants.length)]
  );
  
  const contentQuery = useSmartContentGeneration(type, request);
  
  const getVariantContent = () => {
    if (!contentQuery.content?.data?.suggestions) return null;
    
    return contentQuery.content.data.suggestions.find(
      (suggestion: any) => suggestion.provider === selectedVariant
    );
  };
  
  return {
    ...contentQuery,
    selectedVariant,
    setSelectedVariant,
    variantContent: getVariantContent(),
    variants,
  };
};

// Import useState for A/B testing hook
import { useState } from 'react';
