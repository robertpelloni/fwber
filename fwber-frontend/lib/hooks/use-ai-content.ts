import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

interface GenerationResult {
  suggestions: Array<{
    id: string;
    content: string;
    provider: string;
    confidence: number;
    safety_score: number;
    type: string;
    timestamp: string;
  }>;
  total_providers: number;
  generation_time: string;
  generation_id: string;
}

interface BioParams {
  personality?: string;
  interests?: string[];
  goals?: string;
  style?: 'casual' | 'formal' | 'humorous' | 'romantic';
}

interface PostParams {
  context?: string;
  topic?: string;
}

interface StarterParams {
  target_user_id?: number;
  context?: string;
}

export function useAIContent() {
  const [error, setError] = useState<string | null>(null);

  const generateBio = useMutation({
    mutationFn: async (params: BioParams) => {
      return api.post<GenerationResult>('/content/generate-bio', params);
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to generate bio');
    },
  });

  const generatePosts = useMutation({
    mutationFn: async ({ boardId, params }: { boardId: number; params: PostParams }) => {
      return api.post<GenerationResult>(`/content/generate-posts/${boardId}`, params);
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to generate posts');
    },
  });

  const generateStarters = useMutation({
    mutationFn: async (params: StarterParams) => {
      return api.post<GenerationResult>('/content/generate-starters', params);
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to generate conversation starters');
    },
  });

  return {
    generateBio,
    generatePosts,
    generateStarters,
    error,
    setError,
  };
}
